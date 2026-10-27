<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Backup\Config;

use BC\Modules\Backup\Config\Destination\FtpDestinationConfig;
use BC\Modules\Backup\Config\Destination\GoogleDriveDestinationConfig;
use BC\Modules\Backup\Config\Destination\S3DestinationConfig;
use BC\Modules\Backup\Config\Destination\SftpDestinationConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(GoogleDriveDestinationConfig::class)]
#[CoversClass(S3DestinationConfig::class)]
#[CoversClass(FtpDestinationConfig::class)]
#[CoversClass(SftpDestinationConfig::class)]
final class DestinationConfigTest extends TestCase {
    /**
     * @return iterable<string, array{string, int}>
     */
    public static function provideKeepCounts(): iterable {
        yield 'daily' => ['daily', 11];
        yield 'weekly' => ['weekly', 12];
        yield 'monthly' => ['monthly', 13];
        yield 'yearly' => ['yearly', 14];
        yield 'unknown period keeps all' => ['hourly', 0];
        yield 'case matters' => ['Daily', 0];
    }

    #[DataProvider('provideKeepCounts')]
    public function testGoogleDriveKeepCount(string $period, int $expected): void {
        $config = new GoogleDriveDestinationConfig('Drive', '/key.json', 'folder', 11, 12, 13, 14);

        self::assertSame($expected, $config->getKeepCount($period));
    }

    #[DataProvider('provideKeepCounts')]
    public function testS3KeepCount(string $period, int $expected): void {
        $config = new S3DestinationConfig(
            name: 'S3',
            endpoint: 'https://s3.example',
            bucket: 'bucket',
            accessKeyId: 'id',
            secretAccessKey: 'secret',
            keepDaily: 11,
            keepWeekly: 12,
            keepMonthly: 13,
            keepYearly: 14
        );

        self::assertSame($expected, $config->getKeepCount($period));
    }

    public function testDefaults(): void {
        $drive = new GoogleDriveDestinationConfig('Drive', '/key.json', 'folder');
        $s3 = new S3DestinationConfig('S3', 'https://s3.example', 'bucket', 'id', 'secret');
        $ftp = new FtpDestinationConfig('FTP', 'ftp.example', 21, 'user', 'password');
        $sftp = new SftpDestinationConfig('SFTP', 'sftp.example', 22, 'user');

        self::assertSame([3, 1, 1, 0], [
            $drive->getKeepCount('daily'),
            $drive->getKeepCount('weekly'),
            $drive->getKeepCount('monthly'),
            $drive->getKeepCount('yearly'),
        ]);
        self::assertSame([3, 1, 1, 0], [
            $s3->getKeepCount('daily'),
            $s3->getKeepCount('weekly'),
            $s3->getKeepCount('monthly'),
            $s3->getKeepCount('yearly'),
        ]);
        self::assertSame('Drive', $drive->name);
        self::assertSame('/key.json', $drive->keyFilePath);
        self::assertSame('folder', $drive->folderId);
        self::assertSame('auto', $s3->region);
        self::assertSame('', $s3->prefix);
        self::assertSame(32, $s3->partSizeMb);
        self::assertSame('S3', $s3->name);
        self::assertSame('/', $ftp->basePath);
        self::assertTrue($ftp->passive);
        self::assertFalse($ftp->ssl);
        self::assertNull($ftp->publicUrlBase);
        self::assertSame('FTP', $ftp->name);
        self::assertSame('~', $sftp->basePath);
        self::assertSame('', $sftp->password);
        self::assertSame('', $sftp->keyFilePath);
        self::assertSame('', $sftp->keyPassphrase);
        self::assertNull($sftp->hostKeySha256);
        self::assertNull($sftp->publicUrlBase);
        self::assertSame('SFTP', $sftp->name);
    }
}
