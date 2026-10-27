<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Backup\Service;

use BC\Modules\Backup\Config\BackupConfig;
use BC\Modules\Backup\Config\DatabaseConfig;
use BC\Modules\Backup\Config\Destination\DestinationConfig;
use BC\Modules\Backup\Config\Destination\FtpDestinationConfig;
use BC\Modules\Backup\Config\StaticConfig;
use BC\Modules\Backup\Event\BackupFailedEnvelope;
use BC\Modules\Backup\Event\BackupSucceededEnvelope;
use BC\Modules\Backup\Event\BackupUploadFailedEnvelope;
use BC\Modules\Backup\Exception\BackupException;
use BC\Modules\Backup\Period\IPeriodResolver;
use BC\Modules\Backup\Period\PeriodResolver;
use BC\Modules\Backup\Service\BackupResult;
use BC\Modules\Backup\Service\BackupService;
use BC\Modules\Backup\Service\DestinationResult;
use BC\Modules\Backup\Uploader\DTO\UploadResult;
use BC\Modules\Backup\Uploader\IBackupUploader;
use BC\Modules\Backup\Uploader\IUploaderResolver;
use BC\Tests\Fixture\Backup\BackupConfigProviderStub;
use BC\Tests\Fixture\Backup\BackupLogRepositorySpy;
use BC\Tests\Fixture\Backup\FakeArchiveBuilder;
use BC\Tests\Fixture\Backup\FakeDatabaseDumper;
use BC\Tests\Fixture\Backup\FakeUploader;
use BC\Tests\Fixture\Backup\FakeUploaderResolver;
use BC\Tests\Fixture\Backup\FixedDateConverter;
use BC\Tests\Fixture\Backup\Quietly;
use BC\Tests\Support\EventDispatcherSpy;
use BC\Tests\Support\LoggerSpy;
use BC\Tests\Support\TempDir;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(BackupService::class)]
#[CoversClass(BackupResult::class)]
#[CoversClass(DestinationResult::class)]
#[CoversClass(BackupSucceededEnvelope::class)]
#[CoversClass(BackupFailedEnvelope::class)]
#[CoversClass(BackupUploadFailedEnvelope::class)]
final class BackupServiceTest extends TestCase {
    use Quietly;

    /** A Monday: daily and weekly. */
    private const string NOW = '2026-07-27 04:05:06';

    private const string ARCHIVE = 'bc-backup-20260727-040506.zip';

    private string $dir;

    private string $workDir;

    private FakeDatabaseDumper $dumper;

    private FakeArchiveBuilder $archiveBuilder;

    private BackupLogRepositorySpy $logRepository;

    private EventDispatcherSpy $events;

    private LoggerSpy $logger;

    protected function setUp(): void {
        $this->dir = TempDir::create();
        $this->workDir = "$this->dir/work/backup";
        $this->dumper = new FakeDatabaseDumper();
        $this->archiveBuilder = new FakeArchiveBuilder();
        $this->logRepository = new BackupLogRepositorySpy();
        $this->events = new EventDispatcherSpy();
        $this->logger = new LoggerSpy();

        mkdir("$this->dir/static/photos", 0777, true);
        mkdir("$this->dir/static/music");
        mkdir("$this->dir/static/cache");
        file_put_contents("$this->dir/static/file.txt", 'not a folder');
    }

    protected function tearDown(): void {
        TempDir::remove($this->dir);
    }

    public function testSuccessEverywhere(): void {
        $first = new FakeUploader([
            'daily'  => UploadResult::ok(),
            'weekly' => UploadResult::ok('https://files.example/weekly/' . self::ARCHIVE),
        ]);
        $second = new FakeUploader(['daily' => UploadResult::ok('https://second.example')]);

        $result = $this->runBackup(
            [$this->ftp('First'), $this->ftp('Second')],
            ['First' => $first, 'Second' => $second]
        );

        self::assertTrue($result->success);
        self::assertSame(self::ARCHIVE, $result->archiveName);
        self::assertSame(1234, $result->sizeBytes);
        self::assertSame('https://files.example/weekly/' . self::ARCHIVE, $result->url, 'The first URL wins.');
        self::assertSame(['First', 'Second'], $result->succeededDestinations);
        self::assertSame([], $result->errors);
        self::assertEquals([new DestinationResult('First', true), new DestinationResult('Second', true)], $result->destinationResults);
        self::assertSame([], $result->getFailedDestinations());

        // Every period of every destination.
        self::assertSame(['daily', 'weekly'], array_map(static fn($target): string => $target->periodFolder, $first->targets));
        self::assertSame("$this->workDir/" . self::ARCHIVE, $first->targets[0]->localFile);
        self::assertSame(self::ARCHIVE, $first->targets[0]->remoteName);
        self::assertSame([true, true], $first->archiveExisted);

        // The dump goes into the archive and is deleted, the shipped archive is deleted.
        self::assertSame("$this->workDir/" . self::ARCHIVE . '.database.sql', $this->dumper->dumps[0][1]);
        self::assertSame("$this->workDir/" . self::ARCHIVE . '.database.sql', $this->archiveBuilder->builds[0]['dbDumpFile']);
        self::assertTrue($this->archiveBuilder->builds[0]['dumpExists']);
        self::assertSame("$this->workDir/" . self::ARCHIVE, $this->archiveBuilder->builds[0]['archivePath']);
        self::assertDirectoryExists($this->workDir);
        self::assertSame([], array_values(array_diff((array)scandir($this->workDir), ['.', '..'])));

        self::assertSame([BackupService::EVENT_SUCCEEDED], array_column($this->events->events, 'name'));
        $envelope = $this->events->events[0]['envelope'];
        self::assertEquals(
            new BackupSucceededEnvelope(
                archiveName: self::ARCHIVE,
                sizeBytes: 1234,
                url: 'https://files.example/weekly/' . self::ARCHIVE,
                destinations: ['First', 'Second'],
                durationSeconds: $envelope instanceof BackupSucceededEnvelope ? $envelope->durationSeconds : -1,
                finishedAt: new DateTimeImmutable(self::NOW)
            ),
            $envelope
        );
        self::assertInstanceOf(BackupSucceededEnvelope::class, $envelope);
        self::assertContains($envelope->durationSeconds, [0, 1]);

        self::assertSame([], $this->logger->records);

        self::assertCount(1, $this->logRepository->saved);
        $log = $this->logRepository->saved[0];
        self::assertSame(self::NOW, $log->getCreatedAt()->format('Y-m-d H:i:s'));
        self::assertTrue($log->getSuccess());
        self::assertSame(1234, $log->getSizeBytes());
        self::assertSame(self::ARCHIVE, $log->getArchiveName());
        self::assertSame('https://files.example/weekly/' . self::ARCHIVE, $log->getUrl());
        self::assertSame('First, Second', $log->getDestinations());
        self::assertSame(
            '[{"name":"First","success":true,"error":""},{"name":"Second","success":true,"error":""}]',
            $log->getDestinationResults()
        );
        self::assertSame('', $log->getError());
        self::assertContains($log->getDurationSeconds(), [0, 1]);
    }

    public function testPartialFailure(): void {
        $failing = new FakeUploader([
            'daily'  => UploadResult::fail('Connection refused'),
            'weekly' => UploadResult::ok(null, 'rotation failed'),
        ]);

        $result = $this->runBackup(
            [$this->ftp('Хранилище'), $this->ftp('Broken')],
            ['Хранилище' => new FakeUploader(), 'Broken' => $failing]
        );

        self::assertTrue($result->success);
        self::assertNull($result->url);
        self::assertSame(['Хранилище', 'Broken'], $result->succeededDestinations);
        self::assertSame(['Broken [daily]: Connection refused', 'Broken [weekly]: rotation failed'], $result->errors);
        self::assertEquals(
            [new DestinationResult('Broken', true, ['[daily]: Connection refused', '[weekly]: rotation failed'])],
            $result->getFailedDestinations()
        );

        self::assertSame([BackupService::EVENT_SUCCEEDED, BackupService::EVENT_UPLOAD_FAILED], array_column($this->events->events, 'name'));
        self::assertEquals(
            new BackupUploadFailedEnvelope(
                archiveName: self::ARCHIVE,
                failures: ['Broken' => '[daily]: Connection refused; [weekly]: rotation failed'],
                failedAt: new DateTimeImmutable(self::NOW)
            ),
            $this->events->events[1]['envelope']
        );

        self::assertSame(
            ['[Backup] Upload of ' . self::ARCHIVE . ' to "Broken" failed: [daily]: Connection refused; [weekly]: rotation failed'],
            $this->logger->getMessages('error')
        );

        $log = $this->logRepository->saved[0];
        self::assertSame('', $log->getUrl());
        self::assertSame(
            '[{"name":"Хранилище","success":true,"error":""},'
            . '{"name":"Broken","success":false,"error":"[daily]: Connection refused; [weekly]: rotation failed"}]',
            $log->getDestinationResults()
        );
        self::assertSame('Broken [daily]: Connection refused; Broken [weekly]: rotation failed', $log->getError());
        self::assertFileDoesNotExist("$this->workDir/" . self::ARCHIVE, 'The archive reached a destination.');
    }

    public function testNothingReached(): void {
        $result = $this->runBackup(
            [$this->ftp('Unknown'), $this->ftp('A'), $this->ftp('B')],
            [
                'A' => new FakeUploader(['daily' => new RuntimeException('crashed'), 'weekly' => UploadResult::fail('down')]),
                'B' => new FakeUploader(['daily' => UploadResult::fail('full'), 'weekly' => UploadResult::fail('full')]),
            ]
        );

        self::assertFalse($result->success);
        self::assertSame([], $result->succeededDestinations);
        self::assertSame(
            [
                'No uploader for "Unknown".',
                'A [daily]: crashed',
                'A [weekly]: down',
                'B [daily]: full',
                'B [weekly]: full',
            ],
            $result->errors
        );
        self::assertEquals(
            [
                new DestinationResult('Unknown', false, ['No uploader for "Unknown".']),
                new DestinationResult('A', false, ['[daily]: crashed', '[weekly]: down']),
                new DestinationResult('B', false, ['[daily]: full', '[weekly]: full']),
            ],
            $result->destinationResults
        );

        self::assertFileExists("$this->workDir/" . self::ARCHIVE, 'The archive is kept: it is the only copy.');
        self::assertFileDoesNotExist("$this->workDir/" . self::ARCHIVE . '.database.sql');

        self::assertSame([BackupService::EVENT_FAILED], array_column($this->events->events, 'name'));
        self::assertEquals(
            new BackupFailedEnvelope(
                reason: 'No uploader for "Unknown".; A [daily]: crashed; A [weekly]: down; B [daily]: full; B [weekly]: full',
                failedAt: new DateTimeImmutable(self::NOW),
                archiveName: self::ARCHIVE
            ),
            $this->events->events[0]['envelope']
        );
        self::assertCount(3, $this->logger->getMessages('error'));
        self::assertFalse($this->logRepository->saved[0]->getSuccess());
    }

    public function testNoDestinations(): void {
        $result = $this->runBackup([], []);

        self::assertTrue($result->success);
        self::assertSame(
            ['No upload destinations configured — archive kept locally at ' . "$this->workDir/" . self::ARCHIVE],
            $result->errors
        );
        self::assertSame([], $result->destinationResults);
        self::assertFileExists("$this->workDir/" . self::ARCHIVE);
        self::assertSame([BackupService::EVENT_SUCCEEDED], array_column($this->events->events, 'name'));
        self::assertSame([], $this->logger->records);
    }

    public function testDumpFailure(): void {
        $exception = new BackupException('mysqldump failed (exit code 2): Access denied');
        $this->dumper = new FakeDatabaseDumper($exception);
        $uploader = new FakeUploader();

        $result = $this->runBackup([$this->ftp('A')], ['A' => $uploader]);

        self::assertFalse($result->success);
        self::assertSame(self::ARCHIVE, $result->archiveName);
        self::assertSame(0, $result->sizeBytes);
        self::assertSame(['mysqldump failed (exit code 2): Access denied'], $result->errors);
        self::assertSame([], $result->destinationResults);
        self::assertSame([], $this->archiveBuilder->builds);
        self::assertSame([], $uploader->targets);

        self::assertSame(
            [['level' => 'error', 'message' => '[Backup] Backup failed: mysqldump failed (exit code 2): Access denied', 'context' => ['exception' => $exception]]],
            $this->logger->records
        );
        self::assertEquals(
            new BackupFailedEnvelope('mysqldump failed (exit code 2): Access denied', new DateTimeImmutable(self::NOW), self::ARCHIVE),
            $this->events->events[0]['envelope']
        );
        self::assertSame('mysqldump failed (exit code 2): Access denied', $this->logRepository->saved[0]->getError());
    }

    public function testArchiveFailureKeepsNothingButLogsIt(): void {
        $this->archiveBuilder = new FakeArchiveBuilder(exception: new BackupException('Cannot create archive'));

        $result = $this->runBackup([$this->ftp('A')], ['A' => new FakeUploader()]);

        self::assertFalse($result->success);
        self::assertSame(['Cannot create archive'], $result->errors);
        self::assertFileDoesNotExist("$this->workDir/" . self::ARCHIVE . '.database.sql', 'The dump is deleted anyway.');
    }

    public function testUnexpectedErrorKeepsArchive(): void {
        $resolver = new class implements IUploaderResolver {
            public function resolve(DestinationConfig $destination): IBackupUploader {
                throw new RuntimeException('container is broken');
            }
        };

        $result = $this->runBackup([$this->ftp('A')], [], uploaderResolver: $resolver);

        self::assertFalse($result->success);
        self::assertSame(1234, $result->sizeBytes);
        self::assertSame(['container is broken'], $result->errors);
        self::assertFileExists("$this->workDir/" . self::ARCHIVE);
        self::assertFileDoesNotExist("$this->workDir/" . self::ARCHIVE . '.database.sql');
    }

    public function testEmptyArchive(): void {
        $this->archiveBuilder = new FakeArchiveBuilder(size: 0);

        self::assertSame(0, $this->runBackup([], [])->sizeBytes);
    }

    public function testWorkDirPermissions(): void {
        if (DIRECTORY_SEPARATOR !== '/') {
            self::markTestSkipped('No POSIX permissions.');
        }

        $this->runBackup([], []);

        self::assertSame(0775 & ~umask(), fileperms($this->workDir) & 0777);
    }

    public function testWorkDirCannotBeCreated(): void {
        file_put_contents("$this->dir/work", 'a file');

        $result = self::quietly(fn(): BackupResult => $this->runBackup([], []));

        self::assertFalse($result->success);
        self::assertSame(["Cannot create work directory: $this->workDir"], $result->errors);
        self::assertSame([], $this->dumper->dumps);
    }

    public function testWorkDirWithTrailingSlash(): void {
        $this->runBackup([], [], workDir: "$this->workDir/");

        self::assertSame("$this->workDir/" . self::ARCHIVE, $this->archiveBuilder->builds[0]['archivePath']);
    }

    public function testFailureWithoutErrors(): void {
        $periods = new class implements IPeriodResolver {
            public function resolve(\DateTimeInterface $date): array {
                return [];
            }
        };

        $result = $this->runBackup([$this->ftp('A')], ['A' => new FakeUploader()], periodResolver: $periods);

        self::assertFalse($result->success);
        self::assertSame([], $result->errors);
        self::assertEquals(
            new BackupFailedEnvelope('Unknown error', new DateTimeImmutable(self::NOW), self::ARCHIVE),
            $this->events->events[0]['envelope']
        );
    }

    public function testLogFailureDoesNotMaskOutcome(): void {
        $this->logRepository = new BackupLogRepositorySpy(new RuntimeException('database is gone'));

        $result = $this->runBackup([$this->ftp('A')], ['A' => new FakeUploader()]);

        self::assertTrue($result->success);
        self::assertSame([BackupService::EVENT_SUCCEEDED], array_column($this->events->events, 'name'));
    }

    public function testSelectedStaticFolders(): void {
        $this->runBackup([], [], static: new StaticConfig("$this->dir/static", false, ['music', 'absent', 'photos', 'file.txt'], ['music']));

        self::assertSame(
            ['music' => "$this->dir/static/music", 'photos' => "$this->dir/static/photos"],
            $this->archiveBuilder->builds[0]['staticDirs']
        );
    }

    public function testAllStaticFoldersButExcluded(): void {
        $this->runBackup([], [], static: new StaticConfig("$this->dir/static", true, ['ignored'], ['cache']));

        $staticDirs = $this->archiveBuilder->builds[0]['staticDirs'];
        ksort($staticDirs);
        self::assertSame(['music' => "$this->dir/static/music", 'photos' => "$this->dir/static/photos"], $staticDirs);
    }

    public function testDatabaseConfigIsPassed(): void {
        $this->runBackup([], [], database: $database = new DatabaseConfig(false, ['posts'], []));

        self::assertSame($database, $this->dumper->dumps[0][0]);
    }

    public function testArchivePrefix(): void {
        $result = $this->runBackup([], [], prefix: 'site');

        self::assertSame('site-20260727-040506.zip', $result->archiveName);
    }

    public function testDestinationResult(): void {
        self::assertTrue(new DestinationResult('A', true)->isClean());
        self::assertFalse(new DestinationResult('A', true, ['warning'])->isClean());
        self::assertFalse(new DestinationResult('A', false)->isClean());
    }

    /**
     * @param DestinationConfig[]            $destinations
     * @param array<string, IBackupUploader> $uploaders
     */
    private function runBackup(
        array            $destinations,
        array            $uploaders,
        ?StaticConfig    $static = null,
        ?DatabaseConfig  $database = null,
        ?string          $workDir = null,
        string           $prefix = 'bc-backup',
        ?IPeriodResolver $periodResolver = null,
        ?IUploaderResolver $uploaderResolver = null
    ): BackupResult {
        $config = new BackupConfig(
            database: $database ?? new DatabaseConfig(),
            static: $static ?? new StaticConfig("$this->dir/static"),
            destinations: $destinations,
            workDir: $workDir ?? $this->workDir,
            archivePrefix: $prefix
        );

        return new BackupService(
            new BackupConfigProviderStub($config),
            $this->dumper,
            $this->archiveBuilder,
            $periodResolver ?? new PeriodResolver(),
            $uploaderResolver ?? new FakeUploaderResolver($uploaders),
            $this->logRepository,
            $this->events,
            $this->logger,
            new FixedDateConverter(self::NOW)
        )->run();
    }

    private function ftp(string $name): FtpDestinationConfig {
        return new FtpDestinationConfig($name, 'ftp.example', 21, 'user', 'password');
    }
}
