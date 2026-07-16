<?php

namespace BC\Api\DTO\Fact;

readonly class FactDTO {
    public function __construct(
        public string $title,
        public string $content
    ) {
    }
}
