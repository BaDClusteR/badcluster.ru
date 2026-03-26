<?php

namespace BC\Core\Asset\DTO;

readonly class AssetDTO
{
    public function __construct(
        public string $bundle,
        public string $path,
        public int $priority = 100
    ) {
    }
}