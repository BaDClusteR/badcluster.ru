<?php

namespace BC\Api\DTO\Photo;

readonly class PhotoTagDTO {
    public function __construct(
        public int $id,
        public string $title
    ) {
    }
}