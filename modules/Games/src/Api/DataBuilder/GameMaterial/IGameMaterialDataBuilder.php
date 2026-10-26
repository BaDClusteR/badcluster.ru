<?php

namespace BC\Modules\Games\Api\DataBuilder\GameMaterial;

use BC\Modules\Games\Api\DTO\GameMaterialDTO;
use BC\Modules\Games\Api\DTO\GameMaterialRowDTO;
use BC\Modules\Games\Model\GameMaterial;

interface IGameMaterialDataBuilder {
    /**
     * @param array<string, mixed> $data A row of the database: a material with the title of its game.
     */
    public function buildRow(array $data): GameMaterialRowDTO;

    public function buildEntity(GameMaterial $material): GameMaterialDTO;
}
