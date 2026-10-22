<?php

declare(strict_types=1);

namespace BC\Api\DTO\LogRecord;

readonly class LogRecordDTO {
    public function __construct(
        public int $id,
        public string $level,
        public string $message,
        /** JSON контекста, отформатированный; пустой — контекста не было */
        public string $context,
        public string $trace,
        public string $url,
        public string $ip,
        public string $requestId,
        public int $occurrences,
        public string $firstSeen,
        public string $lastSeen
    ) {
    }
}
