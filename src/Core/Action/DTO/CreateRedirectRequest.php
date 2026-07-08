<?php

declare(strict_types=1);

namespace BC\Core\Action\DTO;

readonly class CreateRedirectRequest {
    public function __construct(
        public string $path,
        public int $code,
        public string $destination = ''
    ) {
    }
}