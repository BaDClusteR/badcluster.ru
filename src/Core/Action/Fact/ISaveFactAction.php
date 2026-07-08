<?php

declare(strict_types=1);

namespace BC\Core\Action\Fact;

use BC\Core\Action\DTO\SaveFactRequest;

interface ISaveFactAction {
    public function run(SaveFactRequest $request): void;
}