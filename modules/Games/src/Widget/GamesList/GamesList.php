<?php

namespace BC\Modules\Games\Widget\GamesList;

use BC\Modules\Games\Core\DTO\GameDTO;
use BC\Widget\AWidget;

class GamesList extends AWidget {
    protected function getTemplatePath(): string {
        return 'modules/Games/List/list.phtml';
    }

    /**
     * @return GameDTO[]
     */
    protected function getGames(): array {
        return (array) ($this->context['games'] ?? []);
    }
}
