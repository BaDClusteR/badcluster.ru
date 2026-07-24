<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Config;

use BC\Modules\Backup\Config\Destination\DestinationConfig;
use BC\Modules\Backup\Config\Destination\FtpDestinationConfig;
use BC\Modules\Backup\Config\Destination\GoogleDriveDestinationConfig;
use BC\Modules\Backup\Config\Destination\SftpDestinationConfig;
use BC\Provider\IPathsProvider;
use Runway\Env\Provider\IEnvVariablesProvider;

/**
 * Assembles the BackupConfig from sensible in-code defaults, overridable via environment
 * variables (see .env.local). Secrets (FTP / Google Drive credentials) live only in the
 * environment, never in the repo.
 *
 * Destinations auto-activate when their credentials are present: set BACKUP_FTP_HOST to
 * enable FTP, BACKUP_SFTP_HOST to enable SFTP, BACKUP_GDRIVE_KEY_FILE to enable Google
 * Drive. Any combination may be set at once.
 */
class BackupConfigProvider implements IBackupConfigProvider {
    public function __construct(
        private readonly IEnvVariablesProvider $env,
        private readonly IPathsProvider $paths,
    ) {}

    public function getConfig(): BackupConfig {
        return new BackupConfig(
            database: $this->buildDatabaseConfig(),
            static: $this->buildStaticConfig(),
            destinations: $this->buildDestinations(),
            workDir: $this->str('BACKUP_WORK_DIR', PROJECT_ROOT . '/log/backup'),
            archivePrefix: $this->str('BACKUP_ARCHIVE_PREFIX', 'bc-backup'),
        );
    }

    private function buildDatabaseConfig(): DatabaseConfig {
        return new DatabaseConfig(
            greedy: $this->bool('BACKUP_DB_GREEDY', true),
            includeTables: $this->csv('BACKUP_DB_INCLUDE_TABLES', []),
            excludeTables: $this->csv('BACKUP_DB_EXCLUDE_TABLES', ['geoip']),
        );
    }

    private function buildStaticConfig(): StaticConfig {
        return new StaticConfig(
            root: rtrim($this->paths->getStaticPath(), '/'),
            greedy: $this->bool('BACKUP_STATIC_GREEDY', false),
            includeFolders: $this->csv(
                'BACKUP_STATIC_INCLUDE',
                ['cringe', 'games', 'media', 'music', 'photos', 'screenshots']
            ),
            excludeFolders: $this->csv('BACKUP_STATIC_EXCLUDE', []),
        );
    }

    /**
     * @return DestinationConfig[]
     */
    private function buildDestinations(): array {
        $destinations = [];

        if ($host = $this->str('BACKUP_FTP_HOST', '')) {
            $destinations[] = new FtpDestinationConfig(
                name: $this->str('BACKUP_FTP_NAME', 'FTP'),
                host: $host,
                port: (int)$this->str('BACKUP_FTP_PORT', '21'),
                username: $this->str('BACKUP_FTP_USER', ''),
                password: $this->str('BACKUP_FTP_PASSWORD', ''),
                basePath: $this->str('BACKUP_FTP_BASE_PATH', '/'),
                passive: $this->bool('BACKUP_FTP_PASSIVE', true),
                ssl: $this->bool('BACKUP_FTP_SSL', false),
                publicUrlBase: $this->str('BACKUP_FTP_PUBLIC_URL', '') ?: null,
            );
        }

        if ($host = $this->str('BACKUP_SFTP_HOST', '')) {
            $destinations[] = new SftpDestinationConfig(
                name: $this->str('BACKUP_SFTP_NAME', 'SFTP'),
                host: $host,
                port: (int)$this->str('BACKUP_SFTP_PORT', '22'),
                username: $this->str('BACKUP_SFTP_USER', ''),
                password: $this->str('BACKUP_SFTP_PASSWORD', ''),
                keyFilePath: $this->str('BACKUP_SFTP_KEY_FILE', ''),
                keyPassphrase: $this->str('BACKUP_SFTP_KEY_PASSPHRASE', ''),
                basePath: $this->str('BACKUP_SFTP_BASE_PATH', '~'),
                hostKeySha256: $this->str('BACKUP_SFTP_HOST_KEY_SHA256', '') ?: null,
                publicUrlBase: $this->str('BACKUP_SFTP_PUBLIC_URL', '') ?: null,
            );
        }

        if ($keyFile = $this->str('BACKUP_GDRIVE_KEY_FILE', '')) {
            $destinations[] = new GoogleDriveDestinationConfig(
                name: $this->str('BACKUP_GDRIVE_NAME', 'Google Drive'),
                keyFilePath: $keyFile,
                folderId: $this->str('BACKUP_GDRIVE_FOLDER_ID', ''),
                keepDaily: (int)$this->str('BACKUP_GDRIVE_KEEP_DAILY', '3'),
                keepWeekly: (int)$this->str('BACKUP_GDRIVE_KEEP_WEEKLY', '1'),
                keepMonthly: (int)$this->str('BACKUP_GDRIVE_KEEP_MONTHLY', '1'),
                keepYearly: (int)$this->str('BACKUP_GDRIVE_KEEP_YEARLY', '0'),
            );
        }

        return $destinations;
    }

    private function str(string $key, string $default): string {
        $value = $this->env->getEnvVariable($key);

        if ($value === null || $value === '') {
            return $default;
        }

        return (string)$value;
    }

    private function bool(string $key, bool $default): bool {
        $value = $this->env->getEnvVariable($key);

        if ($value === null || $value === '') {
            return $default;
        }

        // parse_ini already types "1"/"true"/"on" — normalise anything else too.
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool)$value;
    }

    /**
     * @param string[] $default
     *
     * @return string[]
     */
    private function csv(string $key, array $default): array {
        $value = $this->env->getEnvVariable($key);

        if ($value === null || $value === '') {
            return $default;
        }

        return array_values(
            array_filter(
                array_map('trim', explode(',', (string)$value)),
                static fn(string $item): bool => $item !== ''
            )
        );
    }
}