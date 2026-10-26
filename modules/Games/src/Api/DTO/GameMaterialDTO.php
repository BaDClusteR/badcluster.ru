<?php

namespace BC\Modules\Games\Api\DTO;

use BC\Api\DTO\FileDTO;

readonly class GameMaterialDTO {
    /**
     * @param array<string, mixed> $description       The content of the editor.
     * @param array<string, mixed> $setupInstructions The content of the editor.
     */
    public function __construct(
        public string $title,
        public string $shortTitle,
        public string $slug,
        public int $gameId,
        public ?string $dateAdded,
        public string $annotation,
        public array $description,
        public array $setupInstructions,
        public ?FileDTO $file,
        public string $type,
        public string $url
    ) {
    }
}
