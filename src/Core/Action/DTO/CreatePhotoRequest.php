<?php

declare(strict_types=1);

namespace BC\Core\Action\DTO;

use BC\Model\Media;

readonly class CreatePhotoRequest {
    public function __construct(
        public Media $media,
        public string $alt = '',
        public int $position = 0,
        /** @var \BC\Model\PhotoTag[] */
        public array $tags = []
    ) {
    }
}