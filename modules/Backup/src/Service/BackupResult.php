<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Service;

/**
 * Outcome of a backup run, returned to the caller (e.g. the console command) for display.
 * Persistence and event dispatch happen inside BackupService; this is purely informational.
 */
readonly class BackupResult {
    /**
     * @param string[]            $succeededDestinations names of destinations the archive reached
     * @param string[]            $errors                non-fatal (per-destination) and fatal errors
     * @param DestinationResult[] $destinationResults    every configured destination with its outcome;
     *                                                   empty when the run failed before uploading
     */
    public function __construct(
        public bool $success,
        public string $archiveName,
        public int $sizeBytes,
        public ?string $url,
        public array $succeededDestinations,
        public array $errors,
        public array $destinationResults = [],
    ) {}

    /**
     * @return DestinationResult[] destinations where something went wrong
     */
    public function getFailedDestinations(): array {
        return array_values(array_filter(
            $this->destinationResults,
            static fn(DestinationResult $result): bool => !$result->isClean()
        ));
    }
}
