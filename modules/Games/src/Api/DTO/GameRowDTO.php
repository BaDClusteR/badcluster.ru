<?php

declare(strict_types=1);

namespace BC\Modules\Games\Api\DTO;

use BC\Core\DTO\MediaDTO;

class GameRowDTO {
    public function __construct(
        public int $id,
        public string $title,
        public ?int $releaseYear,
        public ?MediaDTO $cover,
        public int $count
    ) {
    }
}
