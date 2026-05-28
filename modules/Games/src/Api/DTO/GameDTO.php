<?php

declare(strict_types=1);

namespace BC\Modules\Games\Api\DTO;

use BC\Core\DTO\MediaDTO;

class GameDTO {
    public function __construct(
        public string $title,
        public string $slug,
        public string $releaseYear,
        public ?MediaDTO $cover
    ) {
    }
}
