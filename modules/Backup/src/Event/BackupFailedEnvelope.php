<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Event;

use DateTimeImmutable;

/**
 * Dispatched as the "backup.failed" event when a backup cannot be produced or reaches none
 * of its destinations. Subscribed to by the Secret module to send a Slack alert.
 */
readonly class BackupFailedEnvelope {
    public function __construct(
        public string $reason,
        public DateTimeImmutable $failedAt,
        /** Archive name if it got as far as being named, else empty. */
        public string $archiveName = '',
    ) {}
}
