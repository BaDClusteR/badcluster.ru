<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Backup\Api\Endpoint;

use ApiPlatform\Exception\RuntimeInternalErrorException;
use BC\Modules\Backup\Api\DTO\BackupDestinationStatusDTO;
use BC\Modules\Backup\Api\DTO\BackupStatusDTO;
use BC\Modules\Backup\Api\Endpoint\BackupStatus;
use BC\Modules\Backup\Model\BackupLog;
use BC\Modules\Backup\Repository\IBackupLogRepository;
use BC\Tests\Fixture\Backup\SqliteBackupLogRepository;
use DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * SQLite in memory (see SqliteBackupLogRepository).
 */
#[CoversClass(BackupStatus::class)]
#[CoversClass(BackupStatusDTO::class)]
#[CoversClass(BackupDestinationStatusDTO::class)]
final class BackupStatusTest extends TestCase {
    private SqliteBackupLogRepository $repository;

    protected function setUp(): void {
        $this->repository = new SqliteBackupLogRepository();
        $this->repository->ensureSchema();
    }

    public function testNoBackupsYet(): void {
        self::assertEquals(
            new BackupStatusDTO(null, false, 0, '', '', '', []),
            new BackupStatus($this->repository)->get()
        );
        self::assertSame(2, $this->repository->ensureSchemaCalls, 'The table is ensured by the endpoint.');
    }

    public function testLatestBackup(): void {
        $this->save(1785100000, false, 'older.zip', '[]');
        $this->save(1785200000, true, 'newest.zip', '[{"name":"S3","success":true,"error":""},'
            . '{"name":"SFTP","success":false,"error":"[daily]: refused"},"junk",{"name":"Partial"},{}]');
        $this->save(1785150000, false, 'middle.zip', '[]');

        self::assertEquals(
            new BackupStatusDTO(
                lastBackupAt: 1785200000,
                success: true,
                sizeBytes: 2048,
                archiveName: 'newest.zip',
                url: 'https://files.example/newest.zip',
                error: 'SFTP [daily]: refused',
                destinations: [
                    new BackupDestinationStatusDTO('S3', true, ''),
                    new BackupDestinationStatusDTO('SFTP', false, '[daily]: refused'),
                    new BackupDestinationStatusDTO('Partial', false, ''),
                    new BackupDestinationStatusDTO('', false, ''),
                ]
            ),
            new BackupStatus($this->repository)->get()
        );
    }

    /**
     * Records written before destination_results know only where the backup got to.
     */
    public function testLegacyRecord(): void {
        $this->save(1785200000, true, 'a.zip', '', ' S3 , ,SFTP,');

        self::assertEquals(
            [new BackupDestinationStatusDTO('S3', true, ''), new BackupDestinationStatusDTO('SFTP', true, '')],
            new BackupStatus($this->repository)->get()->destinations
        );
    }

    public function testLegacyRecordWithoutDestinations(): void {
        $this->save(1785200000, false, 'a.zip', '', '');

        self::assertSame([], new BackupStatus($this->repository)->get()->destinations);
    }

    public function testDatabaseError(): void {
        $endpoint = new BackupStatus(new class implements IBackupLogRepository {
            public function ensureSchema(): void {
                throw new RuntimeException('no database');
            }

            public function save(BackupLog $log): void {}
        });

        $this->expectException(RuntimeInternalErrorException::class);
        $this->expectExceptionMessage('no database');

        $endpoint->get();
    }

    private function save(int $createdAt, bool $success, string $archive, string $results, string $destinations = 'S3, SFTP'): void {
        $this->repository->save(
            new BackupLog()
                ->setCreatedAt(new DateTime("@$createdAt"))
                ->setSuccess($success)
                ->setSizeBytes(2048)
                ->setArchiveName($archive)
                ->setUrl("https://files.example/$archive")
                ->setDestinations($destinations)
                ->setDestinationResults($results)
                ->setError($success ? 'SFTP [daily]: refused' : 'failed')
                ->setDurationSeconds(1)
        );
    }
}
