<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Config;

use BC\Modules\Backup\Config\Destination\DestinationConfig;

/**
 * The complete, resolved configuration for a backup run. Assembled by IBackupConfigProvider
 * and consumed (read-only) by the backup pipeline.
 */
readonly class BackupConfig {
    /**
     * @param DestinationConfig[] $destinations places the finished archive is uploaded to
     * @param string              $workDir      local directory where the archive is assembled
     * @param string              $archivePrefix filename prefix, e.g. "bc-backup" → bc-backup-20260723-040000.zip
     */
    public function __construct(
        public DatabaseConfig $database,
        public StaticConfig $static,
        public array $destinations,
        public string $workDir,
        public string $archivePrefix = 'bc-backup',
    ) {}
}