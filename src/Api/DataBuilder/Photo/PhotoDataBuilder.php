<?php

declare(strict_types=1);

namespace BC\Api\DataBuilder\Photo;

use BC\Api\DTO\Photo\PhotoDTO;
use BC\Api\DTO\Photo\PhotoRowDTO;
use BC\Core\Converter\IDateConverter;
use BC\Core\DTO\MediaDTO;
use BC\Model\Photo;
use BC\Model\PhotoTag;

readonly class PhotoDataBuilder implements IPhotoDataBuilder {
    public function __construct(
        private IDateConverter $dateConverter
    ) {
    }

    public function buildRow(Photo $photo): PhotoRowDTO {
        return new PhotoRowDTO(
            $photo->getId(),
            $photo->getWebPath(),
            $photo->getWidth(),
            $photo->getHeight(),
            $photo->getPosition(),
            $this->dateConverter->toShortForm($photo->getUploadedAt()),
            $photo->getAlt(),
            basename($photo->getPath())
        );
    }

    public function buildEntity(Photo $photo): PhotoDTO {
        return new PhotoDTO(
        // id 0 = "существующая картинка фотки", а не свежая загрузка из media
            image: new MediaDTO(
                id: 0,
                url: $photo->getWebPath(),
                width: $photo->getWidth(),
                height: $photo->getHeight(),
                mime: $photo->getMime(),
                alt: $photo->getAlt(),
                thumbs: []
            ),
            alt: $photo->getAlt(),
            position: $photo->getPosition(),
            tags: array_map(
                static fn (PhotoTag $tag): string => (string) $tag->getId(),
                $photo->getTags()
            )
        );
    }
}
