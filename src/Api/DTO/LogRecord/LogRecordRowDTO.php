<?php

declare(strict_types=1);

namespace BC\Api\DTO\LogRecord;

readonly class LogRecordRowDTO {
    public function __construct(
        public int $id,
        public string $level,
        public string $message,
        /** Последнее появление, коротко: «22 окт 2026, 17:50» */
        public string $date,
        public int $occurrences
    ) {
    }
}
