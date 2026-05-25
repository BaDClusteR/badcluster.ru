<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Core\Action\Tag;

use BC\Modules\Blog\Core\Action\DTO\CreateTagRequest;
use BC\Modules\Blog\Core\Action\DTO\CreateTagResponse;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;
use Runway\Exception\Exception;

interface ICreateTagAction {
    /**
     * @throws ActionValidationException
     * @throws Exception
     */
    public function run(CreateTagRequest $request): CreateTagResponse;
}
