<?php

namespace BC\Modules\Games\Api\DataBuilder\Game;

use BC\Modules\Games\Api\DTO\GameDTO;
use BC\Modules\Games\Api\DTO\GameRowDTO;
use BC\Modules\Games\Model\Game;

interface IGameDataBuilder {
    public function buildRow(array $game): GameRowDTO;

    public function buildEntity(Game $game): GameDTO;
}
