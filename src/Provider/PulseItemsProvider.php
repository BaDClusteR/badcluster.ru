<?php

namespace BC\Provider;

use BC\Core\Trait\WebsiteSettingsTrait;
use BC\DTO\PulseItemDTO;
use BC\Model\Media;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Model\Exception\ModelException;

class PulseItemsProvider implements IPulseItemsProvider
{
    use WebsiteSettingsTrait;

    /**
     * @return PulseItemDTO[]
     *
     * @throws DBException
     * @throws QueryBuilderException
     * @throws ModelException
     */
    public function getPulseItems(): array
    {
        $webroot = $this->getWebsiteSettings()->getWebRoot();
//        $img = Media::findByUniqueIdentifier(120);
//        Container::getInstance()->getService(IThumbnailsGenerator::class)->generateThumbnails($img, [500, 1000, 2000], true);
//        dd('123');

        return [
            new PulseItemDTO(
                title: 'Редактура',
                url: "$webroot/art/doom3",
                tag: 'Ремастеринг',
                text: 'Причесываю старые переводы в компании AI-редакторов. Правим стиль, убираем опечатки, наводим красоту.',
                status: 'Сейчас в работе:<strong>Doom: Небо в огне. Глава 2</strong>',
                icon: '📖',
                isTall: true
            ),
            new PulseItemDTO(
                title: 'Хомид Йит',
                url: "$webroot/art/sh",
                tag: 'Original Fiction',
                text: 'Триллер о путешествии на нижние уровни Сети. Главный герой ищет ответы в глубинах даркнета, но находит лишь безумие.',
                isSurfaced: true
            ),
            new PulseItemDTO(
                title: 'SBC Band',
                url: "$webroot/music",
                tag: 'Youtube',
                text: 'Проект SBC. Когда нейросети пытаются в музыку. Задорный рок, синтвейв и щепотка Dark Electro.',
                image: Media::findByUniqueIdentifier(68)
            ),
            new PulseItemDTO(
                title: 'Виртуальная фотография',
                url: "$webroot/",
                tag: "Галерея",
                image: Media::findByUniqueIdentifier(93)
            ),
            new PulseItemDTO(
                title: "Впечатления от Cronos: The New Dawn",
                url: "$webroot/blog/cronos",
                tag: "Блог",
                text: "На удивление приятный сурвайвал хоррор про игры со временем. Лично для меня – главный видеоигровой сюрприз 2025-го.",
                image: Media::findByUniqueIdentifier(120)
            )
        ];
    }
}
