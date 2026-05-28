<?php

namespace BC\Modules\Games\Api\DataBuilder\GameMaterial;

use BC\Core\Converter\IDateConverter;
use BC\Modules\Games\Api\DTO\GameMaterialDTO;
use BC\Modules\Games\Api\DTO\GameMaterialRowDTO;
use BC\Modules\Games\Api\DTO\GameMaterialRowGameDTO;
use BC\Modules\Games\Model\GameMaterial;

readonly class GameMaterialDataBuilder implements IGameMaterialDataBuilder {
    public function __construct(
        private IDateConverter $dateConverter
    ) {
    }

    public function buildRow(array $data): GameMaterialRowDTO {
        $timestamp = (int) ($data['date_added'] ?? 0);

        return new GameMaterialRowDTO(
            id: (int) ($data['id'] ?? 0),
            game: new GameMaterialRowGameDTO(
                id: (int) ($data['game_id'] ?? 0),
                title: (string) ($data['game_title'] ?? ''),
            ),
            title: (string) ($data['title'] ?? ''),
            date: $this->dateConverter->toShortForm($timestamp),
            type: (string) ($data['type'] ?? ''),
            annotation: (string) ($data['annotation'] ?? '')
        );
    }

    public function buildEntity(GameMaterial $material): GameMaterialDTO {
        return new GameMaterialDTO(
            title: $material->getTitle(),
            shortTitle: $material->getShortTitle(),
            slug: $material->getSlug(),
            gameId: $material->getGame()->getId(),
            dateAdded: $this->dateConverter->toPickerValue($material->getDateAdded()),
            annotation: $material->getAnnotation(),
            description: $material->getDescription(),
            setupInstructions: $material->getSetupInstructions(),
            file: $material->getFile()?->toFileDTO(),
            type: $material->getType(),
            url: $material->getUrl()
        );
    }
}
