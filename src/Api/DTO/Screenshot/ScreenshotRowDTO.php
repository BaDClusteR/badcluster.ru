<?php

namespace BC\Api\DTO\Screenshot;

readonly class ScreenshotRowDTO {
    public function __construct(
        public int $id,
        public string $url,
        public int $width,
        public int $height,
        public int $position,
        public string $uploadedAt,
        public string $alt
    ) {
    }
}
