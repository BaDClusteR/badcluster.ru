<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Service;

interface IBackupService {
    /**
     * Runs a full backup: dump DB, archive /static, upload to every configured destination,
     * record a log entry and dispatch a success/failure event. Never throws — the outcome is
     * reported through the returned BackupResult.
     */
    public function run(): BackupResult;
}
