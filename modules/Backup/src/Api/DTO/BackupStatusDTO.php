<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Api\DTO;

readonly class BackupStatusDTO {
    public function __construct(
        /** Unix-время последнего бэкапа (секунды); null — бэкапов ещё не было */
        public ?int $lastBackupAt,
        public bool $success,
        public int $sizeBytes,
        public string $error
    ) {
    }
}
