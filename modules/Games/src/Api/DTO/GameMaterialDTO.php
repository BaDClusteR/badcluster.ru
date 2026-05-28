<?php

namespace BC\Modules\Games\Api\DTO;

use BC\Api\DTO\FileDTO;

readonly class GameMaterialDTO {
    public function __construct(
        public string $title,
        public string $shortTitle,
        public string $slug,
        public int $gameId,
        public string $dateAdded,
        public string $annotation,
        public array $description,
        public array $setupInstructions,
        public ?FileDTO $file,
        public string $type,
        public string $url
    ) {
    }
}
