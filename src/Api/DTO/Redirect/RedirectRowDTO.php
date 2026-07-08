<?php

namespace BC\Api\DTO\Redirect;

readonly class RedirectRowDTO {
    public function __construct(
        public int $id,
        public string $path,
        public int $code,
        public string $destination
    ) {
    }
}