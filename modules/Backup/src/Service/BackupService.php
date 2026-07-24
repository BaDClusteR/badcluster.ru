<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Service;

use BC\Modules\Backup\Archiver\IArchiveBuilder;
use BC\Modules\Backup\Config\BackupConfig;
use BC\Modules\Backup\Config\IBackupConfigProvider;
use BC\Modules\Backup\Config\StaticConfig;
use BC\Modules\Backup\Dumper\IDatabaseDumper;
use BC\Modules\Backup\Event\BackupFailedEnvelope;
use BC\Modules\Backup\Event\BackupSucceededEnvelope;
use BC\Modules\Backup\Exception\BackupException;
use BC\Modules\Backup\Model\BackupLog;
use BC\Modules\Backup\Period\IPeriodResolver;
use BC\Modules\Backup\Repository\IBackupLogRepository;
use BC\Modules\Backup\Uploader\DTO\UploadTarget;
use BC\Modules\Backup\Uploader\IUploaderResolver;
use DateTime;
use DateTimeImmutable;
use Runway\Event\IEventDispatcher;
use Throwable;

class BackupService implements IBackupService {
    public const string EVENT_SUCCEEDED = 'backup.succeeded';
    public const string EVENT_FAILED = 'backup.failed';

    public function __construct(
        private readonly IBackupConfigProvider $configProvider,
        private readonly IDatabaseDumper $dumper,
        private readonly IArchiveBuilder $archiveBuilder,
        private readonly IPeriodResolver $periodResolver,
        private readonly IUploaderResolver $uploaderResolver,
        private readonly IBackupLogRepository $logRepository,
        private readonly IEventDispatcher $eventDispatcher,
    ) {}

    public function run(): BackupResult {
        $config = $this->configProvider->getConfig();
        $startedAt = new DateTimeImmutable();
        $startTs = time();

        $archiveName = $config->archivePrefix . '-' . $startedAt->format('Ymd-His') . '.zip';
        $workDir = rtrim($config->workDir, '/');
        $dbDumpFile = $workDir . '/' . $archiveName . '.database.sql';
        $archivePath = $workDir . '/' . $archiveName;

        $errors = [];
        $succeededDestinations = [];
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

            [$success, $succeededDestinations, $url, $uploadErrors] =
                $this->uploadEverywhere($config, $archivePath, $archiveName, $startedAt);
            $errors = array_merge($errors, $uploadErrors);

            // Keep the archive locally only when it wasn't (successfully) shipped anywhere.
            $keepArchive = $config->destinations === [] || !$success;
        } catch (Throwable $e) {
            $success = false;
            $errors[] = $e->getMessage();
            $keepArchive = true;
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
        );

        $this->record($result, $startedAt, $durationSeconds);
        $this->dispatch($result, $startedAt, $durationSeconds);

        return $result;
    }

    /**
     * @return array{0: bool, 1: string[], 2: ?string, 3: string[]}
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
            ];
        }

        $periods = $this->periodResolver->resolve($startedAt);
        $succeeded = [];
        $errors = [];
        $url = null;

        foreach ($config->destinations as $destination) {
            try {
                $uploader = $this->uploaderResolver->resolve($destination);
            } catch (BackupException $e) {
                $errors[] = $e->getMessage();
                continue;
            }

            $destinationOk = false;

            foreach ($periods as $period) {
                try {
                    $uploadResult = $uploader->upload(
                        $destination,
                        new UploadTarget($archivePath, $period, $archiveName)
                    );
                } catch (Throwable $e) {
                    $errors[] = $destination->name . ' [' . $period . ']: ' . $e->getMessage();
                    continue;
                }

                if ($uploadResult->success) {
                    $destinationOk = true;
                    if ($url === null && $uploadResult->url !== null) {
                        $url = $uploadResult->url;
                    }

                    // Non-fatal warning (e.g. rotation failed after a successful upload).
                    if ($uploadResult->error !== null) {
                        $errors[] = $destination->name . ' [' . $period . ']: ' . $uploadResult->error;
                    }
                } else {
                    $errors[] = $destination->name . ' [' . $period . ']: ' . $uploadResult->error;
                }
            }

            if ($destinationOk) {
                $succeeded[] = $destination->name;
            }
        }

        return [$succeeded !== [], $succeeded, $url, $errors];
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
                    finishedAt: new DateTimeImmutable(),
                )
            );

            return;
        }

        $this->eventDispatcher->dispatch(
            self::EVENT_FAILED,
            new BackupFailedEnvelope(
                reason: implode('; ', $result->errors) ?: 'Unknown error',
                failedAt: new DateTimeImmutable(),
                archiveName: $result->archiveName,
            )
        );
    }
}
