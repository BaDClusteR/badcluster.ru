<?php

namespace BC\Api\DTO\PhotoTag;

readonly class PhotoTagRowDTO {
    public function __construct(
        public int $id,
        public string $title,
        public int $photosCount
    ) {
    }
}
