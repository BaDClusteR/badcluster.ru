<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Uploader;

use BC\Modules\Backup\Config\Destination\DestinationConfig;
use BC\Modules\Backup\Uploader\DTO\UploadResult;
use BC\Modules\Backup\Uploader\DTO\UploadTarget;

/**
 * Transports an archive to one destination. Implementations are registered with the
 * "backup.uploader" tag and matched to a destination via supports().
 */
interface IBackupUploader {
    public function supports(DestinationConfig $destination): bool;

    /**
     * Uploads one archive. Must not throw for expected transport failures — return a failed
     * UploadResult instead, so other destinations still run.
     */
    public function upload(DestinationConfig $destination, UploadTarget $target): UploadResult;
}