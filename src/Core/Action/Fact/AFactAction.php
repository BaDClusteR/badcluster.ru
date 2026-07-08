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
        if (trim($request->content) === '') {
            throw new ActionValidationException(['content' => 'Укажите текст факта']);
        }
    }

    protected function syncModel(Fact $fact, CreateFactRequest|SaveFactRequest $request): void {
        $fact->setContent(trim($request->content))
             ->persist();
    }
}