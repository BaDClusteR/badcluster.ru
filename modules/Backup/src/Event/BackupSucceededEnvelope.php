<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Event;

use DateTimeImmutable;

/**
 * Dispatched as the "backup.succeeded" event after an archive is built and uploaded to at
 * least one destination.
 */
readonly class BackupSucceededEnvelope {
    /**
     * @param string[] $destinations names of destinations the archive reached
     */
    public function __construct(
        public string $archiveName,
        public int $sizeBytes,
        public ?string $url,
        public array $destinations,
        public int $durationSeconds,
        public DateTimeImmutable $finishedAt,
    ) {}
}
