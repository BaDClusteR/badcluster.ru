<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Service;

use BC\Core\Converter\IDateConverter;
use BC\Modules\Backup\Archiver\IArchiveBuilder;
use BC\Modules\Backup\Config\BackupConfig;
use BC\Modules\Backup\Config\IBackupConfigProvider;
use BC\Modules\Backup\Config\StaticConfig;
use BC\Modules\Backup\Dumper\IDatabaseDumper;
use BC\Modules\Backup\Event\BackupFailedEnvelope;
use BC\Modules\Backup\Event\BackupSucceededEnvelope;
use BC\Modules\Backup\Event\BackupUploadFailedEnvelope;
use BC\Modules\Backup\Exception\BackupException;
use BC\Modules\Backup\Model\BackupLog;
use BC\Modules\Backup\Period\IPeriodResolver;
use BC\Modules\Backup\Repository\IBackupLogRepository;
use BC\Modules\Backup\Uploader\DTO\UploadTarget;
use BC\Modules\Backup\Uploader\IUploaderResolver;
use DateTime;
use DateTimeImmutable;
use Runway\Event\IEventDispatcher;
use Runway\Logger\ILogger;
use Throwable;

class BackupService implements IBackupService {
    public const string EVENT_SUCCEEDED = 'backup.succeeded';
    public const string EVENT_FAILED = 'backup.failed';
    public const string EVENT_UPLOAD_FAILED = 'backup.upload_failed';

    public function __construct(
        private readonly IBackupConfigProvider $configProvider,
        private readonly IDatabaseDumper $dumper,
        private readonly IArchiveBuilder $archiveBuilder,
        private readonly IPeriodResolver $periodResolver,
        private readonly IUploaderResolver $uploaderResolver,
        private readonly IBackupLogRepository $logRepository,
        private readonly IEventDispatcher $eventDispatcher,
        private readonly ILogger $logger,
        private readonly IDateConverter $dateConverter,
    ) {}

    public function run(): BackupResult {
        $config = $this->configProvider->getConfig();
        $startedAt = $this->now();
        $startTs = time();

        $archiveName = $config->archivePrefix . '-' . $startedAt->format('Ymd-His') . '.zip';
        $workDir = rtrim($config->workDir, '/');
        $dbDumpFile = $workDir . '/' . $archiveName . '.database.sql';
        $archivePath = $workDir . '/' . $archiveName;

        $errors = [];
        $succeededDestinations = [];
        $destinationResults = [];
        $url = null;
        $sizeBytes = 0;
        $success = false;
        $keepArchive = true;

        try {
            $this->prepareWorkDir($workDir);
            $this->dumper->dump($config->database, $dbDumpFile);
            $this->archiveBuilder->build(
                $archivePath,
                $dbDumpFile,
                $this->resolveStaticDirs($config->static)
            );
            $sizeBytes = (int)(@filesize($archivePath) ?: 0);

            [$success, $succeededDestinations, $url, $uploadErrors, $destinationResults] =
                $this->uploadEverywhere($config, $archivePath, $archiveName, $startedAt);
            $errors = array_merge($errors, $uploadErrors);

            // Keep the archive locally only when it wasn't (successfully) shipped anywhere.
            $keepArchive = $config->destinations === [] || !$success;
        } catch (Throwable $e) {
            $success = false;
            $errors[] = $e->getMessage();
            $keepArchive = true;

            $this->logger->error('[Backup] Backup failed: ' . $e->getMessage(), ['exception' => $e]);
        } finally {
            @unlink($dbDumpFile);
            if (!$keepArchive) {
                @unlink($archivePath);
            }
        }

        $durationSeconds = time() - $startTs;
        $result = new BackupResult(
            success: $success,
            archiveName: $archiveName,
            sizeBytes: $sizeBytes,
            url: $url,
            succeededDestinations: $succeededDestinations,
            errors: $errors,
            destinationResults: $destinationResults,
        );

        // One ERROR per destination in trouble, so it also shows up in the dashboard's
        // "needs attention" widget.
        foreach ($result->getFailedDestinations() as $failed) {
            $this->logger->error(sprintf(
                '[Backup] Upload of %s to "%s" failed: %s',
                $archiveName,
                $failed->name,
                implode('; ', $failed->errors)
            ));
        }

        $this->record($result, $startedAt, $durationSeconds);
        $this->dispatch($result, $startedAt, $durationSeconds);

        return $result;
    }

    /**
     * @return array{0: bool, 1: string[], 2: ?string, 3: string[], 4: DestinationResult[]}
     */
    private function uploadEverywhere(
        BackupConfig $config,
        string $archivePath,
        string $archiveName,
        DateTimeImmutable $startedAt,
    ): array {
        if ($config->destinations === []) {
            return [
                true,
                [],
                null,
                ['No upload destinations configured — archive kept locally at ' . $archivePath],
                [],
            ];
        }

        $periods = $this->periodResolver->resolve($startedAt);
        $succeeded = [];
        $errors = [];
        $destinationResults = [];
        $url = null;

        foreach ($config->destinations as $destination) {
            try {
                $uploader = $this->uploaderResolver->resolve($destination);
            } catch (BackupException $e) {
                $errors[] = $e->getMessage();
                $destinationResults[] = new DestinationResult($destination->name, false, [$e->getMessage()]);
                continue;
            }

            $destinationOk = false;
            $destinationErrors = [];

            foreach ($periods as $period) {
                try {
                    $uploadResult = $uploader->upload(
                        $destination,
                        new UploadTarget($archivePath, $period, $archiveName)
                    );
                } catch (Throwable $e) {
                    $destinationErrors[] = '[' . $period . ']: ' . $e->getMessage();
                    continue;
                }

                if ($uploadResult->success) {
                    $destinationOk = true;
                    if ($url === null && $uploadResult->url !== null) {
                        $url = $uploadResult->url;
                    }

                    // Non-fatal warning (e.g. rotation failed after a successful upload).
                    if ($uploadResult->error !== null) {
                        $destinationErrors[] = '[' . $period . ']: ' . $uploadResult->error;
                    }
                } else {
                    $destinationErrors[] = '[' . $period . ']: ' . $uploadResult->error;
                }
            }

            foreach ($destinationErrors as $error) {
                $errors[] = $destination->name . ' ' . $error;
            }

            $destinationResults[] = new DestinationResult($destination->name, $destinationOk, $destinationErrors);

            if ($destinationOk) {
                $succeeded[] = $destination->name;
            }
        }

        return [$succeeded !== [], $succeeded, $url, $errors, $destinationResults];
    }

    /**
     * @return array<string, string> folder name => absolute path
     */
    private function resolveStaticDirs(StaticConfig $config): array {
        $root = $config->root;
        $result = [];

        if ($config->greedy) {
            foreach (scandir($root) ?: [] as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                $path = $root . '/' . $entry;
                if (is_dir($path) && !in_array($entry, $config->excludeFolders, true)) {
                    $result[$entry] = $path;
                }
            }
        } else {
            foreach ($config->includeFolders as $name) {
                $path = $root . '/' . $name;
                if (is_dir($path)) {
                    $result[$name] = $path;
                }
            }
        }

        return $result;
    }

    /**
     * Current time in the site's timezone — for the archive name, the period folders
     * (is it Monday / the 1st yet?) and the event timestamps — whatever PHP's default
     * timezone happens to be.
     */
    private function now(): DateTimeImmutable {
        return DateTimeImmutable::createFromMutable($this->dateConverter->toDateTime(time()));
    }

    private function prepareWorkDir(string $workDir): void {
        if (!is_dir($workDir) && !mkdir($workDir, 0775, true) && !is_dir($workDir)) {
            throw new BackupException('Cannot create work directory: ' . $workDir);
        }
    }

    private function record(BackupResult $result, DateTimeImmutable $startedAt, int $durationSeconds): void {
        try {
            $log = new BackupLog();
            $log->setCreatedAt(DateTime::createFromInterface($startedAt))
                ->setSuccess($result->success)
                ->setSizeBytes($result->sizeBytes)
                ->setArchiveName($result->archiveName)
                ->setUrl($result->url ?? '')
                ->setDestinations(implode(', ', $result->succeededDestinations))
                ->setDestinationResults(json_encode(
                    array_map(
                        static fn(DestinationResult $destination): array => [
                            'name'    => $destination->name,
                            'success' => $destination->isClean(),
                            'error'   => implode('; ', $destination->errors),
                        ],
                        $result->destinationResults
                    ),
                    JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
                ))
                ->setError(implode('; ', $result->errors))
                ->setDurationSeconds($durationSeconds);

            $this->logRepository->save($log);
        } catch (Throwable) {
            // Logging is best-effort; a DB hiccup must not mask the backup outcome itself.
        }
    }

    private function dispatch(BackupResult $result, DateTimeImmutable $startedAt, int $durationSeconds): void {
        if ($result->success) {
            $this->eventDispatcher->dispatch(
                self::EVENT_SUCCEEDED,
                new BackupSucceededEnvelope(
                    archiveName: $result->archiveName,
                    sizeBytes: $result->sizeBytes,
                    url: $result->url,
                    destinations: $result->succeededDestinations,
                    durationSeconds: $durationSeconds,
                    finishedAt: $this->now(),
                )
            );

            if ($failed = $result->getFailedDestinations()) {
                $this->eventDispatcher->dispatch(
                    self::EVENT_UPLOAD_FAILED,
                    new BackupUploadFailedEnvelope(
                        archiveName: $result->archiveName,
                        failures: array_combine(
                            array_map(static fn(DestinationResult $item): string => $item->name, $failed),
                            array_map(static fn(DestinationResult $item): string => implode('; ', $item->errors), $failed)
                        ),
                        failedAt: $this->now(),
                    )
                );
            }

            return;
        }

        $this->eventDispatcher->dispatch(
            self::EVENT_FAILED,
            new BackupFailedEnvelope(
                reason: implode('; ', $result->errors) ?: 'Unknown error',
                failedAt: $this->now(),
                archiveName: $result->archiveName,
            )
        );
    }
}
