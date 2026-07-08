<?php

declare(strict_types=1);

namespace BC\Core\Action\PulseItem;

use BC\Core\Action\DTO\SavePulseItemRequest;

interface ISavePulseItemAction {
    public function run(SavePulseItemRequest $request): void;
}
