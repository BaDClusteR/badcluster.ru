<?php

declare(strict_types=1);

namespace BC\Api\DataBuilder\Fact;

use BC\Api\DTO\Fact\FactDTO;
use BC\Api\DTO\Fact\FactRowDTO;
use BC\Model\Fact;

readonly class FactDataBuilder implements IFactDataBuilder {
    public function buildRow(Fact $fact): FactRowDTO {
        return new FactRowDTO(
            $fact->getId(),
            $fact->getContent()
        );
    }

    public function buildEntity(Fact $fact): FactDTO {
        return new FactDTO(
            content: $fact->getContent()
        );
    }
}
