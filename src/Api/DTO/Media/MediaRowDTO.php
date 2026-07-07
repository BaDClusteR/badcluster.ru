<?php

namespace BC\Api\DTO\Media;

readonly class MediaRowDTO {
    public function __construct(
        public int $id,
        public string $url,
        public int $width,
        public int $height,
        public string $mime,
        public string $alt
    ) {
    }
}