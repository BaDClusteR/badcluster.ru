<?php

declare(strict_types=1);

namespace BC\Core\Action\PulseItem;

use BC\Core\Action\DTO\CreatePulseItemRequest;
use BC\Core\Action\DTO\CreatePulseItemResponse;

interface ICreatePulseItemAction {
    public function run(CreatePulseItemRequest $request): CreatePulseItemResponse;
}
