<?php

namespace BC\Modules\Games\Widget;

use BC\Core\Trait\DateConverterTrait;
use BC\Modules\Games\Model\Game;
use BC\Modules\Games\Model\GameMaterial as GameMaterialModel;
use BC\Widget\AWidget;

class GameMaterial extends AWidget {
    use DateConverterTrait;

    protected function getTemplatePath(): string {
        return 'modules/Games/material.phtml';
    }

    protected function getGame(): ?Game {
        return (($this->context['game'] ?? null) instanceof Game)
            ? $this->context['game']
            : null;
    }

    protected function getMaterial(): ?GameMaterialModel {
        return (($this->context['material'] ?? null) instanceof GameMaterialModel)
            ? $this->context['material']
            : null;
    }
}
