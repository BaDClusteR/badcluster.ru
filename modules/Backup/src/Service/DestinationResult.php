<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Service;

/**
 * Outcome of a backup run for one destination.
 */
readonly class DestinationResult {
    /**
     * @param bool     $reached true when the archive got into at least one period folder
     * @param string[] $errors  failed period uploads and non-fatal warnings (e.g. rotation)
     */
    public function __construct(
        public string $name,
        public bool $reached,
        public array $errors = [],
    ) {}

    /** Everything went fine: every upload succeeded and nothing to warn about. */
    public function isClean(): bool {
        return $this->reached && $this->errors === [];
    }
}
