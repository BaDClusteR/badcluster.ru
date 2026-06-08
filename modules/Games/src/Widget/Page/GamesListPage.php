<?php

declare(strict_types=1);

namespace BC\Modules\Games\Widget\Page;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Modules\Games\Core\DTO\GameDTO;
use BC\Modules\Games\Widget\GamesList\GamesList;
use BC\Widget\AWidget;
use BC\Widget\Page\APage;

class GamesListPage extends APage {
    public function getHeader(): string {
        return 'Игровые материалы';
    }

    public function getMetaDescription(): string {
        return 'Личная коллекция сейвов для пройденных игр, пара статей с разбором игровых секретов и лора.';
    }

    public function getCanonicalUrl(): string {
        return $this->getWebRoot() . '/games';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): array {
        return [
            'Нычка с полезным лутом: личная коллекция сейвов для пройденных игр, пара статей с разбором игровых секретов и лора.',
        ];
    }

    public function getMainWidget(): AWidget {
        return new GamesList(['games' => $this->getGames()]);
    }

    /**
     * @return GameDTO[]
     */
    protected function getGames(): array {
        return (array) ($this->context['games'] ?? []);
    }

    public static function getAssets(): array {
        return [
            new AssetDTO(
                'games',
                'css/modules/Games/games.css'
            ),
        ];
    }

    public function getCssBundles(): array {
        $list = parent::getCssBundles();
        $list[] = 'games';

        return $list;
    }
}
