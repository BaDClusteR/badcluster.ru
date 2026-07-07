<?php

namespace BC\Api\DTO\Screenshot;

use BC\Core\DTO\MediaDTO;

readonly class ScreenshotDTO {
    public function __construct(
        public MediaDTO $image,
        public string $alt,
        public int $position
    ) {
    }
}