<?php

namespace BC\Api\DTO\Photo;

use BC\Core\DTO\MediaDTO;

readonly class PhotoDTO {
    public function __construct(
        public MediaDTO $image,
        public string $alt,
        public int $position,
        /** @var string[] ID тэгов (строками — для мультиселекта в форме) */
        public array $tags
    ) {
    }
}