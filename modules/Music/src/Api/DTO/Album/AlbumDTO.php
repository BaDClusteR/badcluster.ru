<?php

namespace BC\Modules\Music\Api\DTO\Album;

use BC\Core\DTO\MediaDTO;

readonly class AlbumDTO {
    public function __construct(
        public string $title,
        public ?MediaDTO $cover,
        public string $slug,
        public string $genre,
        public string $type,
        public string $releaseDate,
        public string $annotation,
        public string $shortAnnotation,
        public string $musicBy,
        public string $visualBy,
        public string $coverBy,
        public int $position
    ) {
    }
}
