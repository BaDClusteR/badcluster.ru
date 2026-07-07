<?php

namespace BC\Api\DTO\Photo;

use ApiPlatform\Attribute\Docs;

readonly class PhotoTagsDTO {
    public function __construct(
        /** @var PhotoTagDTO[] */
        #[Docs\Property(description: 'Photo tags', childrenType: PhotoTagDTO::class)]
        public array $tags
    ) {
    }
}