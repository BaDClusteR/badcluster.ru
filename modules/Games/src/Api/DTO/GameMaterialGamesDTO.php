<?php

namespace BC\Modules\Games\Api\DTO;

readonly class GameMaterialGamesDTO {
    /**
     * @param GameMaterialGameDTO[] $games
     */
    public function __construct(
        public array $games
    ) {
    }
}
