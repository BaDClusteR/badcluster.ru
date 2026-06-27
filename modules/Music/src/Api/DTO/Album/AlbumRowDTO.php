<?php

namespace BC\Modules\Music\Api\DTO\Album;

use BC\Core\DTO\MediaDTO;
use DateTime;

readonly class AlbumRowDTO {
    public function __construct(
        public int $id,
        public ?MediaDTO $cover,
        public string $title,
        public string $genre,
        public string $type,
        public string $releaseDate,
        public string $tracks
    ) {
    }
}
