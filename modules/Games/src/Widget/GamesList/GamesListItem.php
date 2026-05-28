<?php

namespace BC\Modules\Games\Widget\GamesList;

use BC\Modules\Games\Core\DTO\GameDTO;
use BC\Widget\AWidget;

class GamesListItem extends AWidget {
    protected function getTemplatePath(): string {
        return 'modules/Games/list/item.phtml';
    }

    protected function getGame(): ?GameDTO {
        $result = $this->context['game'] ?? null;

        return ($result instanceof GameDTO)
            ? $result
            : null;
    }
}
