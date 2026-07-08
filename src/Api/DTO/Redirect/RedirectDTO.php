<?php

namespace BC\Api\DTO\Redirect;

readonly class RedirectDTO {
    public function __construct(
        public string $path,
        public int $code,
        public string $destination
    ) {
    }
}