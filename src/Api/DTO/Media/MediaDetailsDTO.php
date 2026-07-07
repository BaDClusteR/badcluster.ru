<?php

namespace BC\Api\DTO\Media;

use ApiPlatform\Attribute\Docs;

readonly class MediaDetailsDTO {
    public function __construct(
        public string $url,
        public string $filename,
        public int $width,
        public int $height,
        public string $mime,
        public string $alt,
        /** @var MediaThumbFileDTO[] */
        #[Docs\Property(description: 'Thumbnails', childrenType: MediaThumbFileDTO::class)]
        public array $thumbs
    ) {
    }
}