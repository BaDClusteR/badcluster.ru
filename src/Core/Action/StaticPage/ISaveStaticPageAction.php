<?php

declare(strict_types=1);

namespace BC\Core\Action\StaticPage;

use BC\Core\Action\DTO\SaveStaticPageRequest;

interface ISaveStaticPageAction {
    public function run(SaveStaticPageRequest $request): void;
}
