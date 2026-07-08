<?php

declare(strict_types=1);

namespace BC\Core\Action\Fact;

use BC\Core\Action\DTO\CreateFactRequest;
use BC\Core\Action\DTO\CreateFactResponse;

interface ICreateFactAction {
    public function run(CreateFactRequest $request): CreateFactResponse;
}