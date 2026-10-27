<?php

declare(strict_types=1);

namespace BC\Tests\Fixture\Backup;

use BC\Modules\Backup\Model\BackupLog;
use BC\Modules\Backup\Repository\BackupLogRepository;
use BC\Tests\Support\SqliteSchema;

/**
 * The repository on SQLite: the real ensureSchema() is MySQL DDL, here the table is the SQLite one of the model
 * (created empty on the first call).
 */
final class SqliteBackupLogRepository extends BackupLogRepository {
    public int $ensureSchemaCalls = 0;

    public function ensureSchema(): void {
        if ($this->ensureSchemaCalls++ === 0) {
            SqliteSchema::createModelTables(BackupLog::class);
        }
    }
}
