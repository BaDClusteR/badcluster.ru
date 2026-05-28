<?php

declare(strict_types=1);

namespace BC\Modules\Games\Core\DTO;

use BC\Modules\Games\Model\Game;
use BC\Modules\Games\Model\GameMaterial;

class GameDTO {
    /**
     * @param GameMaterial[] $materials
     */
    public function __construct(
        public readonly Game $game,
        private array $materials = []
    ) {
    }

    public function addMaterial(GameMaterial $material): static {
        $this->materials[] = $material;

        return $this;
    }

    public function getMaterials(): array {
        return $this->materials;
    }
}
