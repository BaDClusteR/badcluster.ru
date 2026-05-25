<?php

declare(strict_types=1);

namespace BC\Provider;

use BC\DTO\PulseItemDTO;

interface IPulseItemsProvider {
    /**
     * @return PulseItemDTO[]
     */
    public function getPulseItems(): array;
}
