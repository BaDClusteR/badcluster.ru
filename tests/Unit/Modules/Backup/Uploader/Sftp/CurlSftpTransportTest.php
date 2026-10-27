<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Backup\Uploader\Sftp;

use BC\Modules\Backup\Config\Destination\SftpDestinationConfig;
use BC\Modules\Backup\Uploader\DTO\UploadResult;
use BC\Modules\Backup\Uploader\DTO\UploadTarget;
use BC\Modules\Backup\Uploader\Sftp\CurlSftpTransport;
use BC\Tests\Fixture\Backup\CurlSftpTransportSpy;
use BC\Tests\Fixture\Backup\Quietly;
use BC\Tests\Support\TempDir;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(CurlSftpTransport::class)]
final class CurlSftpTransportTest extends TestCase {
    use Quietly;

    private string $dir;

    private string $archive;

    protected function setUp(): void {
        $this->dir = TempDir::create();
        $this->archive = "$this->dir/bc-backup.zip";
        file_put_contents($this->archive, '1234567');
    }

    protected function tearDown(): void {
        TempDir::remove($this->dir);
    }

    public function testDescription(): void {
        $transport = new CurlSftpTransport();

        self::assertSame('curl', $transport->getName());
        $protocols = curl_version()['protocols'] ?? [];
        self::assertIsArray($protocols);
        self::assertSame(in_array('sftp', $protocols, true), $transport->isAvailable());
        self::assertSame('libcurl is built without SFTP support (no SSH library)', $transport->getUnavailabilityReason());
    }

    public function testPasswordAuth(): void {
        $transport = new CurlSftpTransportSpy();

        $result = $transport->upload($this->createDestination(), $this->createTarget());

        self::assertEquals(UploadResult::ok(), $result);
        self::assertCount(1, $transport->calls);
        $options = $transport->calls[0];

        self::assertSame('sftp://sftp.example:2222/srv/backups/daily/bc-backup.zip', $options[CURLOPT_URL]);
        self::assertTrue($options[CURLOPT_UPLOAD]);
        self::assertIsResource($options[CURLOPT_INFILE]);
        self::assertSame(7, $options[CURLOPT_INFILESIZE]);
        self::assertSame(CURLFTP_CREATE_DIR, $options[CURLOPT_FTP_CREATE_MISSING_DIRS]);
        self::assertSame(30, $options[CURLOPT_CONNECTTIMEOUT]);
        self::assertSame(1024, $options[CURLOPT_LOW_SPEED_LIMIT]);
        self::assertSame(60, $options[CURLOPT_LOW_SPEED_TIME]);
        self::assertSame('backup:secret', $options[CURLOPT_USERPWD]);
        self::assertSame(CURLSSH_AUTH_PASSWORD, $options[CURLOPT_SSH_AUTH_TYPES]);
        self::assertArrayNotHasKey(CURLOPT_USERNAME, $options);
        self::assertArrayNotHasKey(CURLOPT_SSH_PRIVATE_KEYFILE, $options);
        self::assertArrayNotHasKey(CURLOPT_KEYPASSWD, $options);
        self::assertArrayNotHasKey(CURLOPT_SSH_HOST_PUBLIC_KEY_SHA256, $options);
    }

    public function testKeyAuthWithPassphraseAndHostKeyPin(): void {
        $transport = new CurlSftpTransportSpy();

        $transport->upload(
            $this->createDestination(keyFilePath: '/keys/id_ed25519', keyPassphrase: 'phrase', hostKeySha256: 'pin='),
            $this->createTarget()
        );

        $options = $transport->calls[0];
        self::assertSame('backup', $options[CURLOPT_USERNAME]);
        self::assertSame(CURLSSH_AUTH_PUBLICKEY, $options[CURLOPT_SSH_AUTH_TYPES]);
        self::assertSame('/keys/id_ed25519', $options[CURLOPT_SSH_PRIVATE_KEYFILE]);
        self::assertSame('phrase', $options[CURLOPT_KEYPASSWD]);
        self::assertSame('pin=', $options[CURLOPT_SSH_HOST_PUBLIC_KEY_SHA256]);
        self::assertArrayNotHasKey(CURLOPT_USERPWD, $options, 'The password is not sent with the key auth.');
    }

    public function testKeyAuthWithoutPassphrase(): void {
        $transport = new CurlSftpTransportSpy();

        $transport->upload($this->createDestination(keyFilePath: '/keys/id_ed25519'), $this->createTarget());

        self::assertArrayNotHasKey(CURLOPT_KEYPASSWD, $transport->calls[0]);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideBasePaths(): iterable {
        yield 'home' => ['~', 'sftp://sftp.example:2222/~/daily/bc-backup.zip'];
        yield 'empty is home' => ['', 'sftp://sftp.example:2222/~/daily/bc-backup.zip'];
        yield 'spaces are home' => ['  ', 'sftp://sftp.example:2222/~/daily/bc-backup.zip'];
        yield 'home-relative' => ['~/backups/', 'sftp://sftp.example:2222/~/backups/daily/bc-backup.zip'];
        yield 'absolute' => ['/srv/backups/', 'sftp://sftp.example:2222/srv/backups/daily/bc-backup.zip'];
        yield 'relative is absolute' => ['srv/backups', 'sftp://sftp.example:2222/srv/backups/daily/bc-backup.zip'];
        yield 'root' => ['/', 'sftp://sftp.example:2222//daily/bc-backup.zip'];
        yield 'padded' => [' /srv ', 'sftp://sftp.example:2222/srv/daily/bc-backup.zip'];
    }

    #[DataProvider('provideBasePaths')]
    public function testUrlPath(string $basePath, string $expectedUrl): void {
        $transport = new CurlSftpTransportSpy();

        $transport->upload($this->createDestination(basePath: $basePath), $this->createTarget());

        self::assertSame($expectedUrl, $transport->calls[0][CURLOPT_URL]);
    }

    public function testPublicUrl(): void {
        $result = new CurlSftpTransportSpy()->upload(
            $this->createDestination(publicUrlBase: 'https://files.example/b/'),
            $this->createTarget()
        );

        self::assertEquals(UploadResult::ok('https://files.example/b/daily/bc-backup.zip'), $result);
    }

    public function testFailedTransfer(): void {
        $transport = new CurlSftpTransportSpy('Authentication failure');

        $result = $transport->upload($this->createDestination(publicUrlBase: 'https://x'), $this->createTarget());

        self::assertEquals(UploadResult::fail('SFTP (curl) upload failed: Authentication failure'), $result);
        self::assertFalse(is_resource($transport->calls[0][CURLOPT_INFILE]), 'The archive is closed.');
    }

    public function testMissingArchive(): void {
        $transport = new CurlSftpTransportSpy();
        $target = new UploadTarget("$this->dir/missing.zip", 'daily', 'missing.zip');

        $result = self::quietly(fn(): UploadResult => $transport->upload($this->createDestination(), $target));

        self::assertEquals(UploadResult::fail("Cannot open archive for reading: $this->dir/missing.zip"), $result);
        self::assertSame([], $transport->calls);
    }

    private function createDestination(
        string  $basePath = '/srv/backups',
        string  $keyFilePath = '',
        string  $keyPassphrase = '',
        ?string $hostKeySha256 = null,
        ?string $publicUrlBase = null
    ): SftpDestinationConfig {
        return new SftpDestinationConfig(
            name: 'SFTP',
            host: 'sftp.example',
            port: 2222,
            username: 'backup',
            password: 'secret',
            keyFilePath: $keyFilePath,
            keyPassphrase: $keyPassphrase,
            basePath: $basePath,
            hostKeySha256: $hostKeySha256,
            publicUrlBase: $publicUrlBase
        );
    }

    private function createTarget(): UploadTarget {
        return new UploadTarget($this->archive, 'daily', 'bc-backup.zip');
    }
}
