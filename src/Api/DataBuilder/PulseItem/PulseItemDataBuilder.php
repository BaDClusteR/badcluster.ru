<?php

declare(strict_types=1);

namespace BC\Api\DataBuilder\PulseItem;

use BC\Api\DTO\PulseItem\PulseItemDTO;
use BC\Api\DTO\PulseItem\PulseItemRowDTO;
use BC\Core\Converter\Media\IMediaConverter;
use BC\Model\PulseItem;

readonly class PulseItemDataBuilder implements IPulseItemDataBuilder {
    public function __construct(
        private IMediaConverter $mediaConverter
    ) {
    }

    public function buildRow(PulseItem $item): PulseItemRowDTO {
        return new PulseItemRowDTO(
            $item->getId(),
            $item->getImage()?->getWebPath(),
            $item->getTitle(),
            $item->getText(),
            $item->getPosition()
        );
    }

    public function buildEntity(PulseItem $item): PulseItemDTO {
        return new PulseItemDTO(
            image: $this->mediaConverter->convertMedia($item->getImage()),
            tag: $item->getTag(),
            title: $item->getTitle(),
            text: $item->getText(),
            statusTitle: $item->getStatusTitle(),
            statusText: $item->getStatusText(),
            icon: $item->getIcon(),
            position: $item->getPosition(),
            url: $item->getUrl() ?? '',
            isTall: $item->getIsTall(),
            isSurfaced: $item->getIsSurfaced()
        );
    }
}
