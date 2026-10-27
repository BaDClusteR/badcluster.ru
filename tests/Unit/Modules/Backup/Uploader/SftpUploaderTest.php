<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Backup\Uploader;

use BC\Modules\Backup\Config\Destination\FtpDestinationConfig;
use BC\Modules\Backup\Config\Destination\SftpDestinationConfig;
use BC\Modules\Backup\Uploader\DTO\UploadResult;
use BC\Modules\Backup\Uploader\DTO\UploadTarget;
use BC\Modules\Backup\Uploader\SftpUploader;
use BC\Tests\Fixture\Backup\FakeSftpTransport;
use BC\Tests\Fixture\Backup\SftpTransportProviderStub;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SftpUploader::class)]
final class SftpUploaderTest extends TestCase {
    public function testSupportsSftpOnly(): void {
        $uploader = new SftpUploader(new SftpTransportProviderStub([]));

        self::assertTrue($uploader->supports($this->createDestination()));
        self::assertFalse($uploader->supports(new FtpDestinationConfig('FTP', 'host', 21, 'user', 'password')));
    }

    public function testUploadsWithAvailableTransport(): void {
        $result = UploadResult::ok('https://files.example/daily/a.zip');
        $transport = new FakeSftpTransport('fake', true, $result);
        $destination = $this->createDestination();
        $target = new UploadTarget('/tmp/a.zip', 'daily', 'a.zip');

        self::assertSame($result, new SftpUploader(new SftpTransportProviderStub([$transport]))->upload($destination, $target));
        self::assertSame([[$destination, $target]], $transport->uploads);
    }

    public function testNoTransport(): void {
        $uploader = new SftpUploader(new SftpTransportProviderStub([
            new FakeSftpTransport('curl', false, unavailabilityReason: 'no SSH in libcurl'),
            new FakeSftpTransport('ext-ssh2', false, unavailabilityReason: 'no extension'),
        ]));

        self::assertEquals(
            UploadResult::fail(
                'No SFTP transport is available in this environment: curl: unavailable (no SSH in libcurl); '
                . 'ext-ssh2: unavailable (no extension)'
            ),
            $uploader->upload($this->createDestination(), new UploadTarget('/tmp/a.zip', 'daily', 'a.zip'))
        );
    }

    public function testRejectsIncompatibleDestination(): void {
        $transport = new FakeSftpTransport();
        $uploader = new SftpUploader(new SftpTransportProviderStub([$transport]));

        self::assertEquals(
            UploadResult::fail('SftpUploader received an incompatible destination.'),
            $uploader->upload(
                new FtpDestinationConfig('FTP', 'host', 21, 'user', 'password'),
                new UploadTarget('/tmp/a.zip', 'daily', 'a.zip')
            )
        );
        self::assertSame([], $transport->uploads);
    }

    private function createDestination(): SftpDestinationConfig {
        return new SftpDestinationConfig('SFTP', 'sftp.example', 22, 'backup');
    }
}
