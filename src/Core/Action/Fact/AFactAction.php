<?php

declare(strict_types=1);

namespace BC\Core\Action\Fact;

use BC\Core\Action\DTO\CreateFactRequest;
use BC\Core\Action\DTO\SaveFactRequest;
use BC\Model\Fact;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;

abstract class AFactAction {
    /**
     * @throws ActionValidationException
     */
    protected function validate(CreateFactRequest|SaveFactRequest $request): void {
        $errors = [];

        if (trim($request->title) === '') {
            $errors['title'] = 'Укажите название факта';
        }

        if (trim($request->content) === '') {
            $errors['content'] = 'Укажите текст факта';
        }

        if ($errors) {
            throw new ActionValidationException($errors);
        }
    }

    protected function syncModel(Fact $fact, CreateFactRequest|SaveFactRequest $request): void {
        $fact->setTitle(trim($request->title))
             ->setContent(trim($request->content))
             ->persist();
    }
}