<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Service;

/**
 * Outcome of a backup run, returned to the caller (e.g. the console command) for display.
 * Persistence and event dispatch happen inside BackupService; this is purely informational.
 */
readonly class BackupResult {
    /**
     * @param string[] $succeededDestinations names of destinations the archive reached
     * @param string[] $errors                non-fatal (per-destination) and fatal errors
     */
    public function __construct(
        public bool $success,
        public string $archiveName,
        public int $sizeBytes,
        public ?string $url,
        public array $succeededDestinations,
        public array $errors,
    ) {}
}
