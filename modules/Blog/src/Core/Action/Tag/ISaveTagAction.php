<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Core\Action\Tag;

use BC\Modules\Blog\Core\Action\DTO\SaveTagRequest;
use BC\Modules\Blog\Core\Action\DTO\SaveTagResponse;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;
use Runway\Exception\Exception;

interface ISaveTagAction {
    /**
     * @throws ActionValidationException
     * @throws Exception
     */
    public function run(SaveTagRequest $request): SaveTagResponse;
}
