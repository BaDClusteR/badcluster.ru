<?php

declare(strict_types=1);

namespace BC\Core\Action\DTO;

readonly class SaveFactRequest {
    public function __construct(
        public int $id,
        public string $content
    ) {
    }
}