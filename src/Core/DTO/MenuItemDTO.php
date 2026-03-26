<?php

namespace BC\Core\DTO;

readonly class MenuItemDTO
{
    public function __construct(
        public string $title,
        public string $url,
        public int $priority = 0
    ) {
    }
}
