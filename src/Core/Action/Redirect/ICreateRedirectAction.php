<?php

declare(strict_types=1);

namespace BC\Core\Action\Redirect;

use BC\Core\Action\DTO\CreateRedirectRequest;
use BC\Core\Action\DTO\CreateRedirectResponse;

interface ICreateRedirectAction {
    public function run(CreateRedirectRequest $request): CreateRedirectResponse;
}
