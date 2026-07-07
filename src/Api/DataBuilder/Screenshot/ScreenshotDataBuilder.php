<?php

declare(strict_types=1);

namespace BC\Api\DataBuilder\Screenshot;

use BC\Api\DTO\Screenshot\ScreenshotDTO;
use BC\Api\DTO\Screenshot\ScreenshotRowDTO;
use BC\Core\Converter\IDateConverter;
use BC\Core\DTO\MediaDTO;
use BC\Model\Screenshot;

readonly class ScreenshotDataBuilder implements IScreenshotDataBuilder {
    public function __construct(
        private IDateConverter $dateConverter
    ) {
    }

    public function buildRow(Screenshot $screenshot): ScreenshotRowDTO {
        return new ScreenshotRowDTO(
            $screenshot->getId(),
            $screenshot->getWebPath(),
            $screenshot->getWidth(),
            $screenshot->getHeight(),
            $screenshot->getPosition(),
            $this->dateConverter->toShortForm($screenshot->getUploadedAt()),
            $screenshot->getAlt()
        );
    }

    public function buildEntity(Screenshot $screenshot): ScreenshotDTO {
        return new ScreenshotDTO(
            // id 0 = "существующая картинка скриншота", а не свежая загрузка из media
            image: new MediaDTO(
                id: 0,
                url: $screenshot->getWebPath(),
                width: $screenshot->getWidth(),
                height: $screenshot->getHeight(),
                mime: $screenshot->getMime(),
                alt: $screenshot->getAlt(),
                thumbs: []
            ),
            alt: $screenshot->getAlt(),
            position: $screenshot->getPosition()
        );
    }
}