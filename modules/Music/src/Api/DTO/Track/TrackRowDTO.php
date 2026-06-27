<?php

namespace BC\Modules\Music\Api\DTO\Track;

readonly class TrackRowDTO {
    public function __construct(
        public int $id,
        public string $title,
        public string $duration
    ) {
    }
}
