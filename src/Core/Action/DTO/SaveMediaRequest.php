<?php

declare(strict_types=1);

namespace BC\Core\Action\DTO;

readonly class SaveMediaRequest {
    public function __construct(
        public int $id,
        public int $width,
        public int $height,
        public string $alt = ''
    ) {
    }
}