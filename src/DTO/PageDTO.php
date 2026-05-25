<?php

declare(strict_types=1);

namespace BC\DTO;

readonly class PageDTO {
    public function __construct(
        public string $title,
        public string $url
    ) {
    }
}
