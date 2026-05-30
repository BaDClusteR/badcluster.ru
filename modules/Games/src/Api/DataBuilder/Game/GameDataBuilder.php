<?php

namespace BC\Modules\Games\Api\DataBuilder\Game;

use BC\Model\Media;
use BC\Modules\Games\Api\DTO\GameDTO;
use BC\Modules\Games\Api\DTO\GameRowDTO;
use BC\Modules\Games\Api\Endpoint\GameMaterial;
use BC\Modules\Games\Model\Game;
use BC\Modules\Games\Model\GameMaterial as GameMaterialModel;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Model\Exception\ModelException;

class GameDataBuilder implements IGameDataBuilder {
    /**
     * @throws DBException
     * @throws ModelException
     * @throws QueryBuilderException
     */
    public function buildRow(array $game): GameRowDTO {
        $cover = Media::findByUniqueIdentifier(
            (int) ($game['cover_id'] ?? 0)
        );

        return new GameRowDTO(
            id: (int) ($game['id'] ?? 0),
            title: (string) ($game['title'] ?? ''),
            releaseYear: (int) ($game['release_year'] ?? 0),
            cover: $cover?->toMediaDTO(),
            count: (int) ($game['count'] ?? 0),
        );
    }

    public function buildEntity(Game $game): GameDTO {
        return new GameDTO(
            title: $game->getTitle(),
            slug: $game->getSlug(),
            releaseYear: (string) $game->getReleaseYear(),
            cover: $game->getCover()?->toMediaDTO(),
        );
    }
}
