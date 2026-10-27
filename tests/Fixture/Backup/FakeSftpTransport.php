<?php

declare(strict_types=1);

namespace BC\Tests\Fixture\Backup;

use BC\Modules\Backup\Config\Destination\SftpDestinationConfig;
use BC\Modules\Backup\Uploader\DTO\UploadResult;
use BC\Modules\Backup\Uploader\DTO\UploadTarget;
use BC\Modules\Backup\Uploader\Sftp\ISftpTransport;

/**
 * An SFTP transport that records the uploads and returns the given result.
 */
final class FakeSftpTransport implements ISftpTransport {
    /** @var list<array{SftpDestinationConfig, UploadTarget}> */
    public array $uploads = [];

    public function __construct(
        private readonly string       $name = 'fake',
        private readonly bool         $isAvailable = true,
        private readonly UploadResult $result = new UploadResult(true),
        private readonly string       $unavailabilityReason = 'not here'
    ) {}

    public function getName(): string {
        return $this->name;
    }

    public function isAvailable(): bool {
        return $this->isAvailable;
    }

    public function getUnavailabilityReason(): string {
        return $this->unavailabilityReason;
    }

    public function upload(SftpDestinationConfig $destination, UploadTarget $target): UploadResult {
        $this->uploads[] = [$destination, $target];

        return $this->result;
    }
}
