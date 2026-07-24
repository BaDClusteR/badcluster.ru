<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Repository;

use BC\Modules\Backup\Model\BackupLog;
use Runway\Singleton\Container;

/**
 * Persists backup log records. Because the project has no migration tooling, the repository
 * creates its own table on demand (CREATE TABLE IF NOT EXISTS), mirroring schema.sql.
 *
 * The driver is obtained via Container::getDataStorageDriver() (the same call QueryBuilder
 * uses) so it is the connected instance — a plain getService()/injected driver is not yet
 * connected in a console/cron context.
 */
class BackupLogRepository implements IBackupLogRepository {
    public function ensureSchema(): void {
        $driver = Container::getInstance()->getDataStorageDriver();
        $table = $driver->getConnectOptions()->tableNamePrefix . 'backup_log';

        $driver->execute(
            'CREATE TABLE IF NOT EXISTS `' . $table . '` ('
            . '`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,'
            . '`created_at` INT UNSIGNED NOT NULL DEFAULT 0,'
            . '`success` TINYINT(1) NOT NULL DEFAULT 0,'
            . '`size_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0,'
            . '`archive_name` VARCHAR(255) NOT NULL DEFAULT \'\','
            . '`url` VARCHAR(1024) NOT NULL DEFAULT \'\','
            . '`destinations` VARCHAR(255) NOT NULL DEFAULT \'\','
            . '`error` TEXT NOT NULL,'
            . '`duration_seconds` INT UNSIGNED NOT NULL DEFAULT 0,'
            . 'PRIMARY KEY (`id`),'
            . 'KEY `idx_created_at` (`created_at`)'
            . ') ENGINE = InnoDB DEFAULT CHARSET = utf8mb4'
        );
    }

    public function save(BackupLog $log): void {
        $this->ensureSchema();
        $log->persist();
    }
}
