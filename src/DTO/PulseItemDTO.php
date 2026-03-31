<?php

namespace BC\DTO;

readonly class PulseItemDTO
{
    public function __construct(
        public string $title,
        public string $url,
        public string $tag,
        public string $text,
        public string $status = '',
        public string $icon = '',
        public bool $isTall = false,
        public bool $isSurfaced = false
    ) {
    }
}
