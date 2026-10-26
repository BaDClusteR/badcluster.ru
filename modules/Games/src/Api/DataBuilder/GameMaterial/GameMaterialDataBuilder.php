<?php

namespace BC\Modules\Games\Api\DataBuilder\GameMaterial;

use BC\Core\Converter\IDateConverter;
use BC\Core\Helper\MixedValue;
use BC\Modules\Games\Api\DTO\GameMaterialDTO;
use BC\Modules\Games\Api\DTO\GameMaterialRowDTO;
use BC\Modules\Games\Api\DTO\GameMaterialRowGameDTO;
use BC\Modules\Games\Model\GameMaterial;
use BC\Modules\Games\Model\GameMaterial as GameMaterialModel;

readonly class GameMaterialDataBuilder implements IGameMaterialDataBuilder {
    public function __construct(
        private IDateConverter $dateConverter
    ) {
    }

    public function buildRow(array $data): GameMaterialRowDTO {
        $timestamp = MixedValue::toInt($data['date_added'] ?? 0);

        if (($data['type'] ?? '') === GameMaterialModel::TYPE_FILE) {
            $title = empty($data['title'])
                ? '[Безымянный материал]'
                : MixedValue::toString($data['title']);
        } else {
            $title = empty($data['short_title'])
                ? '[Безымянная статья]'
                : '[Статья] ' . MixedValue::toString($data['short_title']);
        }

        return new GameMaterialRowDTO(
            id: MixedValue::toInt($data['id'] ?? 0),
            game: new GameMaterialRowGameDTO(
                id: MixedValue::toInt($data['game_id'] ?? 0),
                title: MixedValue::toString($data['game_title'] ?? ''),
            ),
            title: $title,
            game_title: MixedValue::toString($data['game_title'] ?? ''),
            date_added: $timestamp
                ? $this->dateConverter->toShortForm($timestamp)
                : '-',
            type: MixedValue::toString($data['type'] ?? ''),
            annotation: MixedValue::toString($data['annotation'] ?? '')
        );
    }

    public function buildEntity(GameMaterial $material): GameMaterialDTO {
        return new GameMaterialDTO(
            title: $material->getTitle(),
            shortTitle: $material->getShortTitle(),
            slug: $material->getSlug(),
            gameId: $material->getGame()->getId(),
            dateAdded: ($dateAdded = $material->getDateAdded())
                ? $this->dateConverter->toPickerValue($dateAdded)
                : null,
            annotation: $material->getAnnotation(),
            description: $material->getDescription(),
            setupInstructions: $material->getSetupInstructions(),
            file: $material->getFile()?->toFileDTO(),
            type: $material->getType(),
            url: $material->getUrl()
        );
    }
}
