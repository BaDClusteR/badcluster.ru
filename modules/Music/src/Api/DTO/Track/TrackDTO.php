<?php

declare(strict_types=1);

namespace BC\Modules\Music\Api\DTO\Track;

readonly class TrackDTO {
    public function __construct(
        public string $title,
        public bool $explicitLanguage,
        public string $sourceUrl,
        public string $lyrics,
        public string $clipUrl,
        public int $position,
        public string $annotation,
        public SongDTO $song,
        public int $albumId
    ) {
    }
}
