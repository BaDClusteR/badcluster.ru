<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Event;

use DateTimeImmutable;

/**
 * Dispatched as the "backup.upload_failed" event when a backup as a whole succeeded (the
 * archive reached at least one destination), but some destinations failed or reported a
 * problem. When nothing was reached at all, "backup.failed" is dispatched instead — so a
 * single run triggers at most one alert. Subscribed to by the Secret module.
 */
readonly class BackupUploadFailedEnvelope {
    /**
     * @param array<string, string> $failures destination name => what went wrong
     */
    public function __construct(
        public string $archiveName,
        public array $failures,
        public DateTimeImmutable $failedAt,
    ) {}
}
