<?php

namespace BC\Widget\DTO;

readonly class BackLinkDTO {
    public function __construct(
        public string $url,
        public string $text = 'Назад'
    ) {
    }
}
