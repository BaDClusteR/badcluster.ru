<?php

declare(strict_types=1);

namespace BC\Core\Action\DTO;

readonly class CreatePhotoTagRequest {
    public function __construct(
        public string $title,
        public int $position = 0
    ) {
    }
}
