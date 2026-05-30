<?php

namespace BC\Modules\Games\Api\DTO;

readonly class GameMaterialRowDTO {
    public function __construct(
        public int $id,
        public GameMaterialRowGameDTO $game,
        public string $title,
        public string $game_title,
        public string $date_added,
        public string $type,
        public string $annotation
    ) {
    }
}
