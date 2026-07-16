<?php

declare(strict_types=1);

namespace BC\Core\Action\DTO;

readonly class CreateFactRequest {
    public function __construct(
        public string $title,
        public string $content
    ) {
    }
}