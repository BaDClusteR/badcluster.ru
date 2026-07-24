<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Uploader;

use BC\Modules\Backup\Config\Destination\DestinationConfig;
use BC\Modules\Backup\Exception\BackupException;
use Runway\Singleton\Container;

/**
 * Picks the right uploader for a destination among all services tagged "backup.uploader".
 */
class UploaderResolver implements IUploaderResolver {
    public function resolve(DestinationConfig $destination): IBackupUploader {
        /** @var IBackupUploader $uploader */
        foreach (Container::getInstance()->getServicesByTag('backup.uploader') as $uploader) {
            if ($uploader instanceof IBackupUploader && $uploader->supports($destination)) {
                return $uploader;
            }
        }

        throw new BackupException(
            'No uploader registered for destination "' . $destination->name
            . '" (' . $destination::class . ').'
        );
    }
}