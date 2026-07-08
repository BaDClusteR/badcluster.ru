<?php

declare(strict_types=1);

namespace BC\Core\Action\DTO;

use BC\Model\PhotoTag;

readonly class CreatePhotoTagResponse {
    public function __construct(
        public PhotoTag $tag
    ) {
    }
}
