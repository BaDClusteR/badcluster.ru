<?php

declare(strict_types=1);

namespace BC\Tests\Fixture\Backup;

use BC\Modules\Backup\Config\Destination\DestinationConfig;
use BC\Modules\Backup\Exception\BackupException;
use BC\Modules\Backup\Uploader\IBackupUploader;
use BC\Modules\Backup\Uploader\IUploaderResolver;

/**
 * The uploaders by the destination names.
 */
final readonly class FakeUploaderResolver implements IUploaderResolver {
    /**
     * @param array<string, IBackupUploader> $uploaders Destination name => uploader.
     */
    public function __construct(
        private array $uploaders
    ) {}

    public function resolve(DestinationConfig $destination): IBackupUploader {
        return $this->uploaders[$destination->name]
            ?? throw new BackupException("No uploader for \"$destination->name\".");
    }
}
