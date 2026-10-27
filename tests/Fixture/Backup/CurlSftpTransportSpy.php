<?php

declare(strict_types=1);

namespace BC\Tests\Fixture\Backup;

use BC\Modules\Backup\Uploader\Sftp\CurlSftpTransport;

/**
 * Records the curl options instead of the transfer.
 */
final class CurlSftpTransportSpy extends CurlSftpTransport {
    /** @var list<array<int, mixed>> */
    public array $calls = [];

    public function __construct(
        private readonly ?string $error = null
    ) {}

    protected function transfer(array $options): ?string {
        $this->calls[] = $options;

        return $this->error;
    }
}
