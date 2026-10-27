<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Backup\Uploader;

use BC\Modules\Backup\Config\Destination\DestinationConfig;
use BC\Modules\Backup\Config\Destination\FtpDestinationConfig;
use BC\Modules\Backup\Config\Destination\GoogleDriveDestinationConfig;
use BC\Modules\Backup\Config\Destination\S3DestinationConfig;
use BC\Modules\Backup\Config\Destination\SftpDestinationConfig;
use BC\Modules\Backup\Exception\BackupException;
use BC\Modules\Backup\Uploader\FtpUploader;
use BC\Modules\Backup\Uploader\GoogleDriveUploader;
use BC\Modules\Backup\Uploader\S3Uploader;
use BC\Modules\Backup\Uploader\SftpUploader;
use BC\Modules\Backup\Uploader\UploaderResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The uploaders are the tagged services of the container (modules/Backup/config/services.yaml).
 */
#[CoversClass(UploaderResolver::class)]
final class UploaderResolverTest extends TestCase {
    /**
     * @return iterable<string, array{DestinationConfig, class-string}>
     */
    public static function provideDestinations(): iterable {
        yield 'FTP' => [new FtpDestinationConfig('FTP', 'host', 21, 'user', 'password'), FtpUploader::class];
        yield 'SFTP' => [new SftpDestinationConfig('SFTP', 'host', 22, 'user'), SftpUploader::class];
        yield 'Google Drive' => [new GoogleDriveDestinationConfig('Drive', '/key.json', 'folder'), GoogleDriveUploader::class];
        yield 'S3' => [new S3DestinationConfig('S3', 'https://s3.example', 'bucket', 'id', 'secret'), S3Uploader::class];
    }

    /**
     * @param class-string $expected
     */
    #[DataProvider('provideDestinations')]
    public function testResolve(DestinationConfig $destination, string $expected): void {
        self::assertInstanceOf($expected, new UploaderResolver()->resolve($destination));
    }

    public function testUnknownDestination(): void {
        $destination = new readonly class('Carrier pigeon') extends DestinationConfig {};

        $this->expectException(BackupException::class);
        $this->expectExceptionMessage(
            'No uploader registered for destination "Carrier pigeon" (' . $destination::class . ').'
        );

        new UploaderResolver()->resolve($destination);
    }
}
