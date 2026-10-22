<?php

declare(strict_types=1);

namespace BC\Core\Log;

use DateTimeImmutable;

/**
 * Событие ILogRecorder::EVENT_RECORDED: в журнале появилась новая запись (повторы
 * уже известной его не вызывают). На него подписан модуль Secret — шлёт в Slack.
 */
readonly class LogRecordedEnvelope {
    public function __construct(
        public int $id,
        public string $level,
        public string $message,
        /** URL запроса или «CLI: команда» */
        public string $url,
        public DateTimeImmutable $recordedAt,
    ) {
    }
}
