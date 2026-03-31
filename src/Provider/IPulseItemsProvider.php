<?php

namespace BC\Provider;

use BC\DTO\PulseItemDTO;

interface IPulseItemsProvider
{
    /**
     * @return PulseItemDTO[]
     */
    public function getPulseItems(): array;
}
