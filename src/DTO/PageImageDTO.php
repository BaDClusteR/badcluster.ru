<?php

namespace BC\DTO;

readonly class PageImageDTO {
    public function __construct(
        public string $url,
        public int $width,
        public int $height
    ) {
    }
}
