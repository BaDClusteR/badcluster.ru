<?php

namespace BC\Modules\Games\Controller;

use BC\Core\Response\SuccessfulHtmlResponse;
use BC\Core\Trait\Controller404Trait;
use BC\Modules\Games\Core\DTO\GameDTO;
use BC\Modules\Games\Model\Game;
use BC\Modules\Games\Model\GameMaterial;
use BC\Modules\Games\Widget\Page\GameMaterialPage;
use BC\Modules\Games\Widget\Page\GamesListPage;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Exception\Exception;
use Runway\Logger\ILogger;
use Runway\Model\Exception\ModelException;
use Runway\Request\Response;

readonly class Games {
    use Controller404Trait;

    public function __construct(
        private ILogger $logger
    ) {
    }

    /**
     * @return GameDTO[]
     */
    protected function getGamesList(): array {
        $games = [];
        try {
            /** @var Game $game */
            foreach (Game::iterate([], ['title', 'ASC']) as $game) {
                $games[$game->getId()] = new GameDTO($game);
            }

            /** @var GameMaterial $material */
            foreach (GameMaterial::iterate([], ['id', 'ASC']) as $material) {
                $games[$material->getGame()?->getId()]?->addMaterial($material);
            }
        } catch (Exception $e) {
            $this->logger->error(
                sprintf('[%s] Cannot get a list of game materials: %s', __METHOD__, $e->getMessage()),
                [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            return [];
        }

        return $games;
    }

    public function renderGamesList(): Response {
        return new SuccessfulHtmlResponse(
            new GamesListPage()->render([
                'games' => $this->getGamesList(),
            ])
        );
    }

    /**
     * @throws DBException
     * @throws ModelException
     * @throws QueryBuilderException
     */
    public function renderMaterialPage(string $game, string $material): Response {
        $gameModel = Game::findOne(['slug' => $game]);
        if (!$gameModel) {
            return $this->get404Controller()->run();
        }

        $materialModel = GameMaterial::findOne([
            'slug' => $material,
            'game' => $gameModel,
        ]);

        if (!$materialModel?->isFile()) {
            return $this->get404Controller()->run();
        }

        return new SuccessfulHtmlResponse(
            new GameMaterialPage()->render([
                'game'     => $gameModel,
                'material' => $materialModel,
            ])
        );
    }
}
