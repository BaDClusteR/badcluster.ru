<?php

namespace BC\Api\DTO\PhotoTag;

readonly class PhotoTagDTO {
    public function __construct(
        public string $title,
        public int $position
    ) {
    }
}
