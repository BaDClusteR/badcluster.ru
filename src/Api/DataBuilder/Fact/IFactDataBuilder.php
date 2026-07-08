<?php

declare(strict_types=1);

namespace BC\Api\DataBuilder\Fact;

use BC\Api\DTO\Fact\FactDTO;
use BC\Api\DTO\Fact\FactRowDTO;
use BC\Model\Fact;

interface IFactDataBuilder {
    public function buildRow(Fact $fact): FactRowDTO;

    public function buildEntity(Fact $fact): FactDTO;
}
