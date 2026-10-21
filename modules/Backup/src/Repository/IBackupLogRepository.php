<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Repository;

use BC\Modules\Backup\Model\BackupLog;

interface IBackupLogRepository {
    /**
     * Creates the log table if it does not exist yet, or adds missing columns to an existing
     * one. Safe to call repeatedly.
     */
    public function ensureSchema(): void;

    /**
     * Persists a log record. Calls ensureSchema() first so the very first backup on a fresh
     * install still gets logged.
     */
    public function save(BackupLog $log): void;
}
