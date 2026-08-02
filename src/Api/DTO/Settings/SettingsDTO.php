<?php

declare(strict_types=1);

namespace BC\Api\DTO\Settings;

readonly class SettingsDTO {
    public function __construct(
        public string $commentBlacklist
    ) {
    }
}
