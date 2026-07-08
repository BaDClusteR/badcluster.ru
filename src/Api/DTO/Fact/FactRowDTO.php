<?php

namespace BC\Api\DTO\Fact;

readonly class FactRowDTO {
    public function __construct(
        public int $id,
        public string $content
    ) {
    }
}