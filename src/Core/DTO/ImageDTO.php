<?php

namespace BC\Core\DTO;

readonly class ImageDTO
{
    public function __construct(
        public string $path,
        public int $width,
        public int $height,
        public string $mime,
        public int $size,
        public string $md5
    ) {
    }
}
