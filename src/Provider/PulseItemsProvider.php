<?php

namespace BC\Provider;

use BC\DTO\PulseItemDTO;

class PulseItemsProvider implements IPulseItemsProvider
{
    /**
     * @return PulseItemDTO[]
     */
    public function getPulseItems(): array
    {
        return [
            new PulseItemDTO(
                title: 'Редактура',
                url: '/art/doom3',
                tag: 'Ремастеринг',
                text: 'Причесываю старые переводы в компании AI-редакторов. Правим стиль, убираем опечатки, наводим красоту.',
                status: 'Сейчас в работе:<strong>Doom: Небо в огне. Глава 2</strong>',
                icon: '📖',
                isTall: true
            ),
            new PulseItemDTO(
                title: 'Хомид Йит',
                url: '/art/sh',
                tag: 'Original Fiction',
                text: 'Триллер о путешествии на нижние уровни Сети. Главный герой ищет ответы в глубинах даркнета, но находит лишь безумие.',
                isSurfaced: true
            )
        ];
    }
}
