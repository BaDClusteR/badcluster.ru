<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Uploader;

use BC\Modules\Backup\Config\Destination\DestinationConfig;
use BC\Modules\Backup\Exception\BackupException;

interface IUploaderResolver {
    /**
     * Returns the uploader able to handle the given destination.
     *
     * @throws BackupException when no registered uploader supports the destination
     */
    public function resolve(DestinationConfig $destination): IBackupUploader;
}