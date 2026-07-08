<?php

declare(strict_types=1);

namespace BC\Core\Action\PulseItem;

use BC\Core\Action\DTO\CreatePulseItemRequest;
use BC\Core\Action\DTO\SavePulseItemRequest;
use BC\Model\PulseItem;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;

abstract class APulseItemAction {
    /**
     * @throws ActionValidationException
     */
    protected function validate(CreatePulseItemRequest|SavePulseItemRequest $request): void {
        $errors = [];

        if (trim($request->title) === '') {
            $errors['title'] = 'Укажите заголовок';
        }

        if ($request->image && !$request->image->isImage()) {
            $errors['image'] = 'Файл не является изображением';
        }

        if ($errors) {
            throw new ActionValidationException($errors);
        }
    }

    protected function syncModel(PulseItem $item, CreatePulseItemRequest|SavePulseItemRequest $request): void {
        $url = trim($request->url);

        $item->setImage($request->image)
             ->setTag(trim($request->tag))
             ->setTitle(trim($request->title))
             ->setText(trim($request->text))
             ->setStatusTitle(trim($request->statusTitle))
             ->setStatusText(trim($request->statusText))
             ->setIcon(trim($request->icon))
             ->setPosition($request->position)
             ->setUrl($url !== '' ? $url : null)
             ->setIsTall($request->isTall)
             ->setIsSurfaced($request->isSurfaced)
             ->persist();
    }
}
