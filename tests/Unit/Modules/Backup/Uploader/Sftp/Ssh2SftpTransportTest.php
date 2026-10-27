<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Backup\Uploader\Sftp;

use BC\Modules\Backup\Config\Destination\SftpDestinationConfig;
use BC\Modules\Backup\Uploader\DTO\UploadResult;
use BC\Modules\Backup\Uploader\DTO\UploadTarget;
use BC\Modules\Backup\Uploader\Sftp\Ssh2SftpTransport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Only what needs no SSH server: the transport never connects in the tests.
 */
#[CoversClass(Ssh2SftpTransport::class)]
final class Ssh2SftpTransportTest extends TestCase {
    public function testDescription(): void {
        $transport = new Ssh2SftpTransport();

        self::assertSame('ext-ssh2', $transport->getName());
        self::assertSame(extension_loaded('ssh2'), $transport->isAvailable());
        self::assertSame('the ssh2 PECL extension is not installed', $transport->getUnavailabilityReason());
    }

    /**
     * ext-ssh2 cannot verify a SHA256 pin: the upload is refused before any connection.
     */
    public function testRefusesHostKeyPin(): void {
        $result = new Ssh2SftpTransport()->upload(
            new SftpDestinationConfig('SFTP', '192.0.2.1', 22, 'backup', hostKeySha256: 'pin='),
            new UploadTarget('/tmp/a.zip', 'daily', 'a.zip')
        );

        self::assertEquals(
            UploadResult::fail(
                'SFTP (ext-ssh2): SHA256 host-key pinning is not supported by this transport; '
                . 'unset BACKUP_SFTP_HOST_KEY_SHA256 or use the curl transport.'
            ),
            $result
        );
    }

    public function testFailsWithoutExtension(): void {
        if (extension_loaded('ssh2')) {
            self::markTestSkipped('ext-ssh2 is loaded: the upload would connect.');
        }

        $result = new Ssh2SftpTransport()->upload(
            new SftpDestinationConfig('SFTP', '192.0.2.1', 22, 'backup'),
            new UploadTarget('/tmp/a.zip', 'daily', 'a.zip')
        );

        self::assertFalse($result->success);
        self::assertStringStartsWith('SFTP (ext-ssh2) upload failed: Call to undefined function', (string)$result->error);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideAbsoluteBasePaths(): iterable {
        yield 'absolute' => ['/srv/backups/', '/srv/backups'];
        yield 'relative' => ['srv/backups', '/srv/backups'];
        yield 'padded' => ['  /srv  ', '/srv'];
        yield 'root' => ['/', '/'];
    }

    #[DataProvider('provideAbsoluteBasePaths')]
    public function testAbsoluteBaseDir(string $basePath, string $expected): void {
        $baseDir = new ReflectionMethod(Ssh2SftpTransport::class, 'resolveBaseDir')->invoke(
            new Ssh2SftpTransport(),
            null,
            new SftpDestinationConfig('SFTP', 'host', 22, 'backup', basePath: $basePath)
        );

        self::assertSame($expected, $baseDir);
    }

    /**
     * @return iterable<string, array{?string, ?string}>
     */
    public static function providePublicUrls(): iterable {
        yield 'no base' => [null, null];
        yield 'base' => ['https://files.example/b', 'https://files.example/b/weekly/a.zip'];
        yield 'base with a slash' => ['https://files.example/b/', 'https://files.example/b/weekly/a.zip'];
    }

    #[DataProvider('providePublicUrls')]
    public function testPublicUrl(?string $base, ?string $expected): void {
        $url = new ReflectionMethod(Ssh2SftpTransport::class, 'buildPublicUrl')->invoke(
            new Ssh2SftpTransport(),
            new SftpDestinationConfig('SFTP', 'host', 22, 'backup', publicUrlBase: $base),
            new UploadTarget('/tmp/a.zip', 'weekly', 'a.zip')
        );

        self::assertSame($expected, $url);
    }
}
