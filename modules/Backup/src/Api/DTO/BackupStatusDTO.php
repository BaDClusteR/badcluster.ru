<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Api\DTO;

readonly class BackupStatusDTO {
    /**
     * @param BackupDestinationStatusDTO[] $destinations
     */
    public function __construct(
        /** Unix-время последнего бэкапа (секунды); null — бэкапов ещё не было */
        public ?int $lastBackupAt,
        public bool $success,
        public int $sizeBytes,
        public string $archiveName,
        public string $url,
        public string $error,
        public array $destinations
    ) {
    }
}
