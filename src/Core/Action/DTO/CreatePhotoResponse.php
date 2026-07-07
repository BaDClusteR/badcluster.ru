<?php

declare(strict_types=1);

namespace BC\Core\Action\DTO;

use BC\Model\Photo;

readonly class CreatePhotoResponse {
    public function __construct(
        public Photo $photo
    ) {
    }
}