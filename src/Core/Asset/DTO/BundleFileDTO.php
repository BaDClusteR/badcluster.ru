<?php

namespace BC\Core\Asset\DTO;

readonly class BundleFileDTO
{
    public function __construct(
        public string $relativePath,
        public string $absolutePath,
        public int $priority,
    ) {
    }
}
