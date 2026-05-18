<?php

namespace BC\Provider;

class CommentsProvider implements ICommentsProvider {
    /**
     * @inheritDoc
     */
    public function getSuccessMessages(): array {
        return [
            'Спасибо за мысли! Сообщение на модерации — скоро выпущу его в свет.',
            'Принято! Проверю, что вы не робот, и сразу опубликую :)',
            'Achievement Unlocked: Написать автору. Комментарий появится сразу после проверки.',
            'Сигнал принят! Комментарий на модерации — скоро он выйдет из сумрака и появится на сайте.',
            'Принято! Как только допью чай и проверю комментарий — он появится здесь.'
        ];
    }

    public function isPageExist(string $pageType, int $pageId): bool {
        return false;
    }
}
