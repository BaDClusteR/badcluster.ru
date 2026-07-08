<?php

declare(strict_types=1);

namespace BC\Core\Action\DTO;

readonly class SaveRedirectRequest {
    public function __construct(
        public int $id,
        public string $path,
        public int $code,
        public string $destination = ''
    ) {
    }
}