<?php

declare(strict_types=1);

namespace BC\Provider;

use BC\Core\Trait\WebsiteSettingsTrait;
use BC\DTO\PulseItemDTO;
use BC\Model\Media;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Model\Exception\ModelException;

class PulseItemsProvider implements IPulseItemsProvider {
    use WebsiteSettingsTrait;

    /**
     * @return PulseItemDTO[]
     *
     * @throws DBException
     * @throws QueryBuilderException
     * @throws ModelException
     */
    public function getPulseItems(): array {
        $items = $this->getPulseItemsUnsorted();

        usort(
            $items,
            static fn (PulseItemDTO $a, PulseItemDTO $b): int => $a->position <=> $b->position
        );

        return $items;
    }

    /**
     * @throws DBException
     * @throws ModelException
     * @throws QueryBuilderException
     */
    protected function getPulseItemsUnsorted(): array {
        $webroot = $this->getWebsiteSettings()->getWebRoot();

        return [
            new PulseItemDTO(
                title: 'Редактура',
                url: "$webroot/art/doom3",
                tag: 'Ремастеринг',
                text: 'Причесываю старые переводы в компании AI-редакторов. Правим стиль, убираем опечатки, наводим красоту.',
                status: 'Сейчас в работе:<strong>Doom: Небо в огне. Глава 2</strong>',
                icon: '📖',
                isTall: true,
                position: 100
            ),
            new PulseItemDTO(
                title: 'Хомид Йит',
                url: "$webroot/art/sh",
                tag: 'Original Fiction',
                text: 'Триллер о путешествии на нижние уровни Сети. Главный герой ищет ответы в глубинах даркнета, но находит лишь безумие.',
                isSurfaced: true,
                position: 200
            ),
            new PulseItemDTO(
                title: 'SBC Band',
                url: "$webroot/music",
                tag: 'Youtube',
                text: 'Проект SBC. Когда нейросети пытаются в музыку. Задорный рок, синтвейв и щепотка Dark Electro.',
                image: Media::findByUniqueIdentifier(68),
                position: 300
            ),
            new PulseItemDTO(
                title: 'Виртуальная фотография',
                url: "$webroot/",
                tag: 'Галерея',
                image: Media::findByUniqueIdentifier(93),
                position: 400
            ),
        ];
    }
}
