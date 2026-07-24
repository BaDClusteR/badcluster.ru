<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Config\Destination;

/**
 * Base class for a place a finished archive is uploaded to.
 *
 * A concrete subclass carries the transport-specific settings (credentials, paths).
 * One backup run may target several destinations at once.
 */
abstract readonly class DestinationConfig {
    public function __construct(
        /** Human-readable label used in logs and notifications. */
        public string $name,
    ) {}
}