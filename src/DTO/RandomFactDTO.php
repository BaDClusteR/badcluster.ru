<?php

namespace BC\DTO;

readonly class RandomFactDTO {
    public function __construct(
        public string $title,
        public string $content
    ) {
    }
}
