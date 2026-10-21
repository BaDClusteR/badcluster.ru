<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Api\DTO;

readonly class BackupDestinationStatusDTO {
    public function __construct(
        public string $name,
        /** true — загрузилось без единой ошибки и предупреждения */
        public bool $success,
        public string $error
    ) {
    }
}
