-- Schema for the Backup module log table.
--
-- The runway ORM stores DateTime and bool properties as integers, and derives
-- snake_case column names from camelCase properties. The column types below match
-- what BC\Modules\Backup\Model\BackupLog writes.
--
-- The table name is prefixed with DB_PREFIX (bc_) — adjust if your prefix differs.
-- BackupLogRepository::ensureSchema() runs an equivalent CREATE TABLE IF NOT EXISTS
-- automatically before the first insert, so applying this file by hand is optional.
-- It also adds columns introduced later to an existing table:
--
--   ALTER TABLE `bc_backup_log` ADD COLUMN `destination_results` TEXT NOT NULL AFTER `destinations`;

CREATE TABLE IF NOT EXISTS `bc_backup_log` (
    `id`               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `created_at`       INT UNSIGNED    NOT NULL DEFAULT 0,  -- unix timestamp
    `success`          TINYINT(1)      NOT NULL DEFAULT 0,
    `size_bytes`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `archive_name`     VARCHAR(255)    NOT NULL DEFAULT '',
    `url`              VARCHAR(1024)   NOT NULL DEFAULT '',
    `destinations`     VARCHAR(255)    NOT NULL DEFAULT '',
    `destination_results` TEXT         NOT NULL,          -- JSON, see BackupLog::$destinationResults
    `error`            TEXT            NOT NULL,
    `duration_seconds` INT UNSIGNED    NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_created_at` (`created_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;