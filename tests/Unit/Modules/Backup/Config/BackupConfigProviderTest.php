<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Backup\Config;

use BC\Modules\Backup\Config\BackupConfig;
use BC\Modules\Backup\Config\BackupConfigProvider;
use BC\Modules\Backup\Config\DatabaseConfig;
use BC\Modules\Backup\Config\Destination\FtpDestinationConfig;
use BC\Modules\Backup\Config\Destination\GoogleDriveDestinationConfig;
use BC\Modules\Backup\Config\Destination\S3DestinationConfig;
use BC\Modules\Backup\Config\Destination\SftpDestinationConfig;
use BC\Modules\Backup\Config\StaticConfig;
use BC\Tests\Fixture\Backup\PathsProviderStub;
use BC\Tests\Support\EnvStub;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(BackupConfigProvider::class)]
#[CoversClass(BackupConfig::class)]
#[CoversClass(DatabaseConfig::class)]
#[CoversClass(StaticConfig::class)]
final class BackupConfigProviderTest extends TestCase {
    public function testDefaults(): void {
        $config = $this->getConfig([]);

        self::assertSame(PROJECT_ROOT . '/log/backup', $config->workDir);
        self::assertSame('bc-backup', $config->archivePrefix);
        self::assertSame([], $config->destinations);

        self::assertTrue($config->database->greedy);
        self::assertSame([], $config->database->includeTables);
        self::assertSame(['geoip'], $config->database->excludeTables);

        self::assertSame('/srv/site/static', $config->static->root);
        self::assertFalse($config->static->greedy);
        self::assertSame(['cringe', 'games', 'media', 'music', 'photos', 'screenshots'], $config->static->includeFolders);
        self::assertSame([], $config->static->excludeFolders);
    }

    public function testEmptyValuesAreDefaults(): void {
        $config = $this->getConfig([
            'BACKUP_WORK_DIR'          => '',
            'BACKUP_ARCHIVE_PREFIX'    => '',
            'BACKUP_DB_GREEDY'         => '',
            'BACKUP_DB_EXCLUDE_TABLES' => '',
            'BACKUP_FTP_HOST'          => '',
            'BACKUP_SFTP_HOST'         => '',
            'BACKUP_GDRIVE_KEY_FILE'   => '',
            'BACKUP_S3_BUCKET'         => '',
        ]);

        self::assertSame(PROJECT_ROOT . '/log/backup', $config->workDir);
        self::assertSame('bc-backup', $config->archivePrefix);
        self::assertTrue($config->database->greedy);
        self::assertSame(['geoip'], $config->database->excludeTables);
        self::assertSame([], $config->destinations);
    }

    public function testOverrides(): void {
        $config = $this->getConfig([
            'BACKUP_WORK_DIR'          => '/tmp/work',
            'BACKUP_ARCHIVE_PREFIX'    => 'site',
            'BACKUP_DB_GREEDY'         => 'false',
            'BACKUP_DB_INCLUDE_TABLES' => ' posts, ,comments ,',
            'BACKUP_DB_EXCLUDE_TABLES' => 'geoip,log_records',
            'BACKUP_STATIC_GREEDY'     => 'yes',
            'BACKUP_STATIC_INCLUDE'    => 'media',
            'BACKUP_STATIC_EXCLUDE'    => 'cache, tmp',
        ], '/srv/site/static/');

        self::assertSame('/tmp/work', $config->workDir);
        self::assertSame('site', $config->archivePrefix);
        self::assertFalse($config->database->greedy);
        self::assertSame(['posts', 'comments'], $config->database->includeTables);
        self::assertSame(['geoip', 'log_records'], $config->database->excludeTables);
        self::assertSame('/srv/site/static', $config->static->root, 'The trailing slash is trimmed.');
        self::assertTrue($config->static->greedy);
        self::assertSame(['media'], $config->static->includeFolders);
        self::assertSame(['cache', 'tmp'], $config->static->excludeFolders);
    }

    /**
     * @return iterable<string, array{mixed, bool, bool}>
     */
    public static function provideBooleans(): iterable {
        yield 'typed true' => [true, false, true];
        yield 'typed false' => [false, true, false];
        yield 'typed 1' => [1, false, true];
        yield 'typed 0' => [0, true, false];
        yield '"on"' => ['on', false, true];
        yield '"off"' => ['off', true, false];
        yield '"no"' => ['no', true, false];
        yield '"TRUE"' => ['TRUE', false, true];
        yield 'not a boolean, not empty' => ['maybe', false, true];
        yield 'missing' => [null, true, true];
        yield 'missing, false by default' => [null, false, false];
    }

    #[DataProvider('provideBooleans')]
    public function testBooleans(mixed $value, bool $default, bool $expected): void {
        // BACKUP_DB_GREEDY is true by default, BACKUP_STATIC_GREEDY is false.
        $variable = $default ? 'BACKUP_DB_GREEDY' : 'BACKUP_STATIC_GREEDY';
        $config = $this->getConfig($value === null ? [] : [$variable => $value]);

        self::assertSame($expected, $default ? $config->database->greedy : $config->static->greedy);
    }

    public function testFtpDestination(): void {
        $config = $this->getConfig([
            'BACKUP_FTP_HOST'       => 'ftp.example',
            'BACKUP_FTP_NAME'       => 'Storage box',
            'BACKUP_FTP_PORT'       => 2121,
            'BACKUP_FTP_USER'       => 'user',
            'BACKUP_FTP_PASSWORD'   => 'secret',
            'BACKUP_FTP_BASE_PATH'  => '/backups',
            'BACKUP_FTP_PASSIVE'    => false,
            'BACKUP_FTP_SSL'        => true,
            'BACKUP_FTP_PUBLIC_URL' => 'https://files.example/backups',
        ]);

        self::assertEquals(
            [
                new FtpDestinationConfig(
                    name: 'Storage box',
                    host: 'ftp.example',
                    port: 2121,
                    username: 'user',
                    password: 'secret',
                    basePath: '/backups',
                    passive: false,
                    ssl: true,
                    publicUrlBase: 'https://files.example/backups'
                ),
            ],
            $config->destinations
        );
    }

    public function testFtpDestinationDefaults(): void {
        $config = $this->getConfig(['BACKUP_FTP_HOST' => 'ftp.example']);

        self::assertEquals(
            [new FtpDestinationConfig('FTP', 'ftp.example', 21, '', '', '/', true, false, null)],
            $config->destinations
        );
    }

    public function testSftpDestination(): void {
        $config = $this->getConfig([
            'BACKUP_SFTP_HOST'            => 'sftp.example',
            'BACKUP_SFTP_NAME'            => 'Backup server',
            'BACKUP_SFTP_PORT'            => '2222',
            'BACKUP_SFTP_USER'            => 'backup',
            'BACKUP_SFTP_PASSWORD'        => 'secret',
            'BACKUP_SFTP_KEY_FILE'        => '/keys/id_ed25519',
            'BACKUP_SFTP_KEY_PASSPHRASE'  => 'phrase',
            'BACKUP_SFTP_BASE_PATH'       => '/srv/backups',
            'BACKUP_SFTP_HOST_KEY_SHA256' => 'abc=',
            'BACKUP_SFTP_PUBLIC_URL'      => 'https://files.example',
        ]);

        self::assertEquals(
            [
                new SftpDestinationConfig(
                    name: 'Backup server',
                    host: 'sftp.example',
                    port: 2222,
                    username: 'backup',
                    password: 'secret',
                    keyFilePath: '/keys/id_ed25519',
                    keyPassphrase: 'phrase',
                    basePath: '/srv/backups',
                    hostKeySha256: 'abc=',
                    publicUrlBase: 'https://files.example'
                ),
            ],
            $config->destinations
        );
    }

    public function testSftpDestinationDefaults(): void {
        $config = $this->getConfig(['BACKUP_SFTP_HOST' => 'sftp.example']);

        self::assertEquals(
            [new SftpDestinationConfig('SFTP', 'sftp.example', 22, '', '', '', '', '~', null, null)],
            $config->destinations
        );
    }

    public function testGoogleDriveDestination(): void {
        $config = $this->getConfig([
            'BACKUP_GDRIVE_KEY_FILE'     => '/keys/drive.json',
            'BACKUP_GDRIVE_NAME'         => 'Drive',
            'BACKUP_GDRIVE_FOLDER_ID'    => 'folder-id',
            'BACKUP_GDRIVE_KEEP_DAILY'   => '7',
            'BACKUP_GDRIVE_KEEP_WEEKLY'  => 4,
            'BACKUP_GDRIVE_KEEP_MONTHLY' => '12',
            'BACKUP_GDRIVE_KEEP_YEARLY'  => '5',
        ]);

        self::assertEquals(
            [new GoogleDriveDestinationConfig('Drive', '/keys/drive.json', 'folder-id', 7, 4, 12, 5)],
            $config->destinations
        );
    }

    public function testGoogleDriveDestinationDefaults(): void {
        $config = $this->getConfig(['BACKUP_GDRIVE_KEY_FILE' => '/keys/drive.json']);

        self::assertEquals(
            [new GoogleDriveDestinationConfig('Google Drive', '/keys/drive.json', '', 3, 1, 1, 0)],
            $config->destinations
        );
    }

    public function testS3Destination(): void {
        $config = $this->getConfig([
            'BACKUP_S3_BUCKET'            => 'bucket',
            'BACKUP_S3_NAME'              => 'R2',
            'BACKUP_S3_ENDPOINT'          => 'https://account.r2.example',
            'BACKUP_S3_ACCESS_KEY_ID'     => 'key-id',
            'BACKUP_S3_SECRET_ACCESS_KEY' => 'secret',
            'BACKUP_S3_REGION'            => 'eu-west-1',
            'BACKUP_S3_PREFIX'            => 'bc',
            'BACKUP_S3_PART_SIZE_MB'      => '64',
            'BACKUP_S3_KEEP_DAILY'        => '7',
            'BACKUP_S3_KEEP_WEEKLY'       => '4',
            'BACKUP_S3_KEEP_MONTHLY'      => '12',
            'BACKUP_S3_KEEP_YEARLY'       => '2',
        ]);

        self::assertEquals(
            [
                new S3DestinationConfig(
                    name: 'R2',
                    endpoint: 'https://account.r2.example',
                    bucket: 'bucket',
                    accessKeyId: 'key-id',
                    secretAccessKey: 'secret',
                    region: 'eu-west-1',
                    prefix: 'bc',
                    partSizeMb: 64,
                    keepDaily: 7,
                    keepWeekly: 4,
                    keepMonthly: 12,
                    keepYearly: 2
                ),
            ],
            $config->destinations
        );
    }

    public function testS3DestinationDefaults(): void {
        $config = $this->getConfig(['BACKUP_S3_BUCKET' => 'bucket']);

        self::assertEquals(
            [new S3DestinationConfig('S3', '', 'bucket', '', '', 'auto', '', 32, 3, 1, 1, 0)],
            $config->destinations
        );
    }

    public function testAllDestinationsInOrder(): void {
        $config = $this->getConfig([
            'BACKUP_S3_BUCKET'       => 'bucket',
            'BACKUP_GDRIVE_KEY_FILE' => '/keys/drive.json',
            'BACKUP_SFTP_HOST'       => 'sftp.example',
            'BACKUP_FTP_HOST'        => 'ftp.example',
        ]);

        self::assertSame(
            [FtpDestinationConfig::class, SftpDestinationConfig::class, GoogleDriveDestinationConfig::class, S3DestinationConfig::class],
            array_map(static fn(object $destination): string => $destination::class, $config->destinations)
        );
    }

    /**
     * @param array<string, mixed> $env
     */
    private function getConfig(array $env, string $staticPath = '/srv/site/static'): BackupConfig {
        return new BackupConfigProvider(new EnvStub($env), new PathsProviderStub($staticPath))->getConfig();
    }
}
