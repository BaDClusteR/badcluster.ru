<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Backup\Uploader;

use BC\Modules\Backup\Config\Destination\FtpDestinationConfig;
use BC\Modules\Backup\Config\Destination\SftpDestinationConfig;
use BC\Modules\Backup\Uploader\DTO\UploadResult;
use BC\Modules\Backup\Uploader\DTO\UploadTarget;
use BC\Modules\Backup\Uploader\FtpUploader;
use BC\Tests\Fixture\Backup\FtpUploaderSpy;
use BC\Tests\Fixture\Backup\Quietly;
use BC\Tests\Support\TempDir;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(FtpUploader::class)]
#[CoversClass(UploadResult::class)]
final class FtpUploaderTest extends TestCase {
    use Quietly;

    private string $dir;

    private string $archive;

    protected function setUp(): void {
        $this->dir = TempDir::create();
        $this->archive = "$this->dir/bc-backup.zip";
        file_put_contents($this->archive, '12345');
    }

    protected function tearDown(): void {
        TempDir::remove($this->dir);
    }

    public function testSupportsFtpOnly(): void {
        $uploader = new FtpUploader();

        self::assertTrue($uploader->supports($this->createDestination()));
        self::assertFalse($uploader->supports(new SftpDestinationConfig('SFTP', 'host', 22, 'user')));
    }

    public function testRejectsIncompatibleDestination(): void {
        $uploader = $this->createUploader();
        $result = $uploader->upload(new SftpDestinationConfig('SFTP', 'host', 22, 'user'), $this->createTarget());

        self::assertEquals(UploadResult::fail('FtpUploader received an incompatible destination.'), $result);
        self::assertSame([], $uploader->calls);
    }

    public function testMissingArchive(): void {
        $uploader = $this->createUploader();
        $target = new UploadTarget("$this->dir/missing.zip", 'daily', 'missing.zip');

        $result = self::quietly(fn(): UploadResult => $uploader->upload($this->createDestination(), $target));

        self::assertEquals(UploadResult::fail("Cannot open archive for reading: $this->dir/missing.zip"), $result);
        self::assertSame([], $uploader->calls);
    }

    public function testCurlOptions(): void {
        $uploader = $this->createUploader();

        $result = $uploader->upload($this->createDestination(), $this->createTarget());

        self::assertEquals(UploadResult::ok(), $result);
        self::assertCount(1, $uploader->calls);
        $options = $uploader->calls[0];

        self::assertSame('ftp://ftp.example:2121/backups/daily/bc-backup-20260727.zip', $options[CURLOPT_URL]);
        self::assertTrue($options[CURLOPT_UPLOAD]);
        self::assertIsResource($options[CURLOPT_INFILE]);
        self::assertSame(5, $options[CURLOPT_INFILESIZE]);
        self::assertSame('user:p@ss:word', $options[CURLOPT_USERPWD]);
        self::assertSame(CURLFTP_CREATE_DIR, $options[CURLOPT_FTP_CREATE_MISSING_DIRS]);
        self::assertSame(CURLUSESSL_NONE, $options[CURLOPT_USE_SSL]);
        self::assertSame(CURLFTPAUTH_DEFAULT, $options[CURLOPT_FTPSSLAUTH]);
        self::assertSame(30, $options[CURLOPT_CONNECTTIMEOUT]);
        self::assertSame(1024, $options[CURLOPT_LOW_SPEED_LIMIT]);
        self::assertSame(60, $options[CURLOPT_LOW_SPEED_TIME]);
        self::assertArrayNotHasKey(CURLOPT_FTPPORT, $options, 'Passive mode needs no port.');
        self::assertArrayNotHasKey(CURLOPT_TIMEOUT, $options, 'No hard time limit.');
    }

    public function testSslAndActiveMode(): void {
        $uploader = $this->createUploader();

        $uploader->upload($this->createDestination(ssl: true, passive: false), $this->createTarget());

        $options = $uploader->calls[0];
        self::assertSame('ftps://ftp.example:2121/backups/daily/bc-backup-20260727.zip', $options[CURLOPT_URL]);
        self::assertSame(CURLUSESSL_ALL, $options[CURLOPT_USE_SSL]);
        self::assertSame('-', $options[CURLOPT_FTPPORT]);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideBasePaths(): iterable {
        yield 'root' => ['/', 'ftp://ftp.example:2121/daily/bc-backup-20260727.zip'];
        yield 'empty' => ['', 'ftp://ftp.example:2121/daily/bc-backup-20260727.zip'];
        yield 'slashes around' => ['/a/b/', 'ftp://ftp.example:2121/a/b/daily/bc-backup-20260727.zip'];
        yield 'relative' => ['backups', 'ftp://ftp.example:2121/backups/daily/bc-backup-20260727.zip'];
    }

    #[DataProvider('provideBasePaths')]
    public function testRemotePath(string $basePath, string $expectedUrl): void {
        $uploader = $this->createUploader();

        $uploader->upload($this->createDestination(basePath: $basePath), $this->createTarget());

        self::assertSame($expectedUrl, $uploader->calls[0][CURLOPT_URL]);
    }

    public function testPublicUrl(): void {
        $result = $this->createUploader()->upload(
            $this->createDestination(publicUrlBase: 'https://files.example/backups/'),
            $this->createTarget()
        );

        self::assertEquals(UploadResult::ok('https://files.example/backups/daily/bc-backup-20260727.zip'), $result);
    }

    public function testFailedTransfer(): void {
        $uploader = $this->createUploader('Connection refused');

        $result = $uploader->upload(
            $this->createDestination(publicUrlBase: 'https://files.example'),
            $this->createTarget()
        );

        self::assertEquals(UploadResult::fail('FTP upload failed: Connection refused'), $result);
    }

    public function testArchiveIsClosedAfterUpload(): void {
        $uploader = $this->createUploader();
        $uploader->upload($this->createDestination(), $this->createTarget());

        self::assertFalse(is_resource($uploader->calls[0][CURLOPT_INFILE]));
    }

    private function createDestination(
        bool    $ssl = false,
        bool    $passive = true,
        string  $basePath = '/backups/',
        ?string $publicUrlBase = null
    ): FtpDestinationConfig {
        return new FtpDestinationConfig(
            name: 'FTP',
            host: 'ftp.example',
            port: 2121,
            username: 'user',
            password: 'p@ss:word',
            basePath: $basePath,
            passive: $passive,
            ssl: $ssl,
            publicUrlBase: $publicUrlBase
        );
    }

    private function createTarget(): UploadTarget {
        return new UploadTarget($this->archive, 'daily', 'bc-backup-20260727.zip');
    }

    private function createUploader(?string $error = null): FtpUploaderSpy {
        return new FtpUploaderSpy($error);
    }
}
