<?php

declare(strict_types=1);

namespace BC\Tests\Fixture\Backup;

use BC\Modules\Backup\Uploader\Sftp\ISftpTransport;
use BC\Modules\Backup\Uploader\Sftp\SftpTransportProvider;

/**
 * The provider with the given transports instead of the tagged services of the container.
 */
final class SftpTransportProviderStub extends SftpTransportProvider {
    /**
     * @param ISftpTransport[] $transports
     */
    public function __construct(
        private readonly array $transports
    ) {}

    protected function getTransports(): array {
        return $this->transports;
    }
}
