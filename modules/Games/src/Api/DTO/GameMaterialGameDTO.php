<?php

namespace BC\Modules\Games\Api\DTO;

use BC\Core\DTO\MediaDTO;

readonly class GameMaterialGameDTO {
    public function __construct(
        public int $id,
        public string $title,
        public ?MediaDTO $cover,
        public string $slug
    ) {
    }
}
