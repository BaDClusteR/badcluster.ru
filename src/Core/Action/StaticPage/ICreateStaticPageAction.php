<?php

declare(strict_types=1);

namespace BC\Core\Action\StaticPage;

use BC\Core\Action\DTO\CreateStaticPageRequest;
use BC\Core\Action\DTO\CreateStaticPageResponse;

interface ICreateStaticPageAction {
    public function run(CreateStaticPageRequest $request): CreateStaticPageResponse;
}
