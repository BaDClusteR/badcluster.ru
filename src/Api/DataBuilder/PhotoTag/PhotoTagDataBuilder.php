<?php

declare(strict_types=1);

namespace BC\Api\DataBuilder\PhotoTag;

use BC\Api\DTO\PhotoTag\PhotoTagDTO;
use BC\Api\DTO\PhotoTag\PhotoTagRowDTO;
use BC\Model\PhotoTag;

readonly class PhotoTagDataBuilder implements IPhotoTagDataBuilder {
    public function buildRow(PhotoTag $tag, int $photosCount): PhotoTagRowDTO {
        return new PhotoTagRowDTO(
            $tag->getId(),
            $tag->getTitle(),
            $photosCount
        );
    }

    public function buildEntity(PhotoTag $tag): PhotoTagDTO {
        return new PhotoTagDTO(
            title: $tag->getTitle(),
            position: $tag->getPosition()
        );
    }
}
