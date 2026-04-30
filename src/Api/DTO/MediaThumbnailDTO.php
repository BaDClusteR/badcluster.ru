<?php

namespace BC\Api\DTO;

readonly class MediaThumbnailDTO
{
    public function __construct(
        public int $width,
        public int $height,
        public string $mime,
        public string $url
    ) {
    }
}
