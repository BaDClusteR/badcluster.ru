<?php

declare(strict_types=1);

namespace BC\Core\Action\DTO;

use BC\Model\Media;

readonly class SaveScreenshotRequest {
    public function __construct(
        public int $id,
        public string $alt = '',
        public int $position = 0,
        /** Новая картинка вместо текущей; NULL — оставить текущую. */
        public ?Media $media = null
    ) {
    }
}