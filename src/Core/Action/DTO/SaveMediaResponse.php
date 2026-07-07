<?php

declare(strict_types=1);

namespace BC\Core\Action\DTO;

use BC\Model\Media;

readonly class SaveMediaResponse {
    public function __construct(
        public Media $media
    ) {
    }
}