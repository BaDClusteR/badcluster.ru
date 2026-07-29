<?php

declare(strict_types=1);

namespace BC\Modules\Games\Controller;

use BC\Core\Response\SuccessfulHtmlResponse;
use BC\Core\Trait\Controller404Trait;
use BC\Model\Media;
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
     * Список собирается фиксированным числом запросов (игры, обложки,
     * тумбнейлы, материалы) вместо жадной гидрации связей на каждую строку:
     * сырые строки маппятся без FK-колонок, а связанные сущности грузятся
     * пачками и раздаются вручную.
     *
     * @return GameDTO[]
     */
    protected function getGamesList(): array {
        $games = [];
        try {
            $gameRows = Game::getQueryBuilder()->orderBy('title', 'ASC')->getResults();

            $coverIds = array_filter(
                array_map(
                    static fn (array $row): int => (int) ($row['cover_id'] ?? 0),
                    $gameRows
                )
            );

            // parent_id обложек не гидрируем: списку не нужен родитель картинки
            $coversById = $this->loadEntitiesById(Media::class, $coverIds, ['parent_id']);
            Media::preloadThumbnails($coversById);

            foreach ($gameRows as $row) {
                $coverId = (int) ($row['cover_id'] ?? 0);
                unset($row['cover_id']);

                $game = new Game()->map($row);

                if ($cover = $coversById[$coverId] ?? null) {
                    $game->setCover($cover);
                }

                $games[$game->getId()] = new GameDTO($game);
            }

            // file_id вырезаем совсем: файл материала в списке не используется
            $materialRows = GameMaterial::getQueryBuilder()->orderBy('id', 'ASC')->getResults();

            foreach ($materialRows as $row) {
                $gameDTO = $games[(int) ($row['game_id'] ?? 0)] ?? null;

                if (!$gameDTO) {
                    continue;
                }

                unset($row['game_id'], $row['file_id']);

                $gameDTO->addMaterial(
                    new GameMaterial()->map($row)->setGame($gameDTO->game)
                );
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

    /**
     * @template T of \Runway\Model\AEntity
     *
     * @param class-string<T> $entityFQN
     * @param int[]           $ids
     * @param string[]        $stripColumns FK-колонки, которые не нужно гидрировать
     *
     * @return array<int, T> по id
     *
     * @throws Exception
     */
    private function loadEntitiesById(string $entityFQN, array $ids, array $stripColumns = []): array {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        if (!$ids) {
            return [];
        }

        $qb = $entityFQN::getQueryBuilder();
        $entities = [];

        foreach ($qb->where($qb->expr()->in('id', $ids))->getResults() as $row) {
            $id = (int) $row['id'];

            foreach ($stripColumns as $column) {
                unset($row[$column]);
            }

            $entities[$id] = new $entityFQN()->map($row);
        }

        return $entities;
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
