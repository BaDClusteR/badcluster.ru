<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Backup\Uploader\Sftp;

use BC\Modules\Backup\Uploader\Sftp\CurlSftpTransport;
use BC\Modules\Backup\Uploader\Sftp\SftpTransportProvider;
use BC\Modules\Backup\Uploader\Sftp\Ssh2SftpTransport;
use BC\Tests\Fixture\Backup\FakeSftpTransport;
use BC\Tests\Fixture\Backup\SftpTransportProviderStub;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

#[CoversClass(SftpTransportProvider::class)]
final class SftpTransportProviderTest extends TestCase {
    public function testFirstAvailableTransport(): void {
        $available = new FakeSftpTransport('second', true);
        $provider = new SftpTransportProviderStub([
            new FakeSftpTransport('first', false),
            $available,
            new FakeSftpTransport('third', true),
        ]);

        self::assertSame($available, $provider->getTransport());
    }

    public function testNoAvailableTransport(): void {
        $provider = new SftpTransportProviderStub([new FakeSftpTransport('first', false)]);

        self::assertNull($provider->getTransport());
        self::assertNull(new SftpTransportProviderStub([])->getTransport());
    }

    public function testDiagnostics(): void {
        $provider = new SftpTransportProviderStub([
            new FakeSftpTransport('curl', false, unavailabilityReason: 'no libssh2'),
            new FakeSftpTransport('ext-ssh2', true),
        ]);

        self::assertSame(['curl: unavailable (no libssh2)', 'ext-ssh2: available'], $provider->getDiagnostics());
    }

    public function testDiagnosticsWithoutTransports(): void {
        self::assertSame(['no SFTP transports registered'], new SftpTransportProviderStub([])->getDiagnostics());
    }

    /**
     * The transports of the container (config/services.yaml of the module), the preferred one first.
     */
    public function testTransportsOfContainerByPriority(): void {
        $transports = new ReflectionMethod(SftpTransportProvider::class, 'getTransports')
            ->invoke(new SftpTransportProvider());

        self::assertIsArray($transports);
        self::assertSame(
            [CurlSftpTransport::class, Ssh2SftpTransport::class],
            array_map(static fn(mixed $transport): string => get_debug_type($transport), $transports)
        );
        self::assertSame([0, 1], array_keys($transports));
    }
}
