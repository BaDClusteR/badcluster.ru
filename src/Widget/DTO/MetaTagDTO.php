<?php

declare(strict_types=1);

namespace BC\Widget\DTO;

readonly class MetaTagDTO
{
    public function __construct(
        public string $name,
        public string $content
    ) {
    }
}