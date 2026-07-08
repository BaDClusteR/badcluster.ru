<?php

declare(strict_types=1);

namespace BC\Core\Action\Redirect;

use BC\Core\Action\DTO\SaveRedirectRequest;

interface ISaveRedirectAction {
    public function run(SaveRedirectRequest $request): void;
}
