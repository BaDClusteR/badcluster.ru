<?php

declare(strict_types=1);

namespace BC\Core\Action\DTO;

readonly class SavePhotoTagRequest {
    public function __construct(
        public int $id,
        public string $title,
        public int $position = 0
    ) {
    }
}
