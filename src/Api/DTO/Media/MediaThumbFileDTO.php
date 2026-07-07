<?php

namespace BC\Api\DTO\Media;

readonly class MediaThumbFileDTO {
    public function __construct(
        public int $id,
        public string $filename,
        public string $url,
        public string $mime,
        public int $width,
        public int $height,
        public string $sizeHumanReadable
    ) {
    }
}