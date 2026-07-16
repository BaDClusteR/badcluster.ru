<?php

declare(strict_types=1);

namespace BC\Api\DataBuilder\PulseItem;

use BC\Api\DTO\PulseItem\PulseItemDTO;
use BC\Api\DTO\PulseItem\PulseItemRowDTO;
use BC\DTO\PulseItemDTO as ProviderPulseItemDTO;
use BC\Model\PulseItem;

interface IPulseItemDataBuilder {
    public function buildRow(PulseItem $item): PulseItemRowDTO;

    public function buildAutoRow(ProviderPulseItemDTO $item, int $id): PulseItemRowDTO;

    public function buildEntity(PulseItem $item): PulseItemDTO;
}
