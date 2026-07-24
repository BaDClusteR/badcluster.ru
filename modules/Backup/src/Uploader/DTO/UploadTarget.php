<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Uploader\DTO;

/**
 * A single upload request: one local archive into one period sub-folder of one destination.
 */
readonly class UploadTarget {
    public function __construct(
        /** Absolute path of the archive on disk. */
        public string $localFile,
        /** Period sub-folder to place it in ('daily', 'weekly', …). */
        public string $periodFolder,
        /** Filename to store it under remotely. */
        public string $remoteName,
    ) {}
}