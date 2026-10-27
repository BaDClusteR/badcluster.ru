<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Backup\Repository;

use BC\Modules\Backup\Model\BackupLog;
use BC\Modules\Backup\Repository\BackupLogRepository;
use BC\Tests\Fixture\Backup\SqliteBackupLogRepository;
use DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * SQLite in memory: ensureSchema() is replaced (its DDL is MySQL), save() is the real one.
 */
#[CoversClass(BackupLogRepository::class)]
#[CoversClass(BackupLog::class)]
final class BackupLogRepositoryTest extends TestCase {
    public function testSaveEnsuresSchemaAndPersists(): void {
        $repository = new SqliteBackupLogRepository();
        $log = new BackupLog()
            ->setCreatedAt(new DateTime('@1785124800'))
            ->setSuccess(true)
            ->setSizeBytes(5_000_000_000)
            ->setArchiveName('bc-backup.zip')
            ->setUrl('https://files.example/bc-backup.zip')
            ->setDestinations('S3, SFTP')
            ->setDestinationResults('[]')
            ->setError('')
            ->setDurationSeconds(42);

        $repository->save($log);
        $repository->save(new BackupLog()->setCreatedAt(new DateTime('@1785211200')));

        self::assertSame(2, $repository->ensureSchemaCalls);

        $found = BackupLog::findByUniqueIdentifier($log->getId());
        self::assertNotNull($found);
        self::assertSame(1785124800, $found->getCreatedAt()->getTimestamp());
        self::assertTrue($found->getSuccess());
        self::assertSame(5_000_000_000, $found->getSizeBytes());
        self::assertSame('bc-backup.zip', $found->getArchiveName());
        self::assertSame('https://files.example/bc-backup.zip', $found->getUrl());
        self::assertSame('S3, SFTP', $found->getDestinations());
        self::assertSame('[]', $found->getDestinationResults());
        self::assertSame(42, $found->getDurationSeconds());
        self::assertCount(2, BackupLog::find());
    }
}
