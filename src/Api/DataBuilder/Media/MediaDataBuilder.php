<?php

declare(strict_types=1);

namespace BC\Api\DataBuilder\Media;

use BC\Api\DTO\Media\MediaDetailsDTO;
use BC\Api\DTO\Media\MediaRowDTO;
use BC\Api\DTO\Media\MediaThumbFileDTO;
use BC\Core\Formatter\IFormatter;
use BC\Model\Media;

readonly class MediaDataBuilder implements IMediaDataBuilder {
    public function __construct(
        private IFormatter $formatter
    ) {
    }

    public function buildRow(Media $media): MediaRowDTO {
        return new MediaRowDTO(
            $media->getId(),
            $media->getWebPath(),
            $media->getWidth(),
            $media->getHeight(),
            $media->getMime(),
            $media->getAlt()
        );
    }

    public function buildEntity(Media $media): MediaDetailsDTO {
        return new MediaDetailsDTO(
            url: $media->getWebPath(),
            filename: basename($media->getPath()),
            width: $media->getWidth(),
            height: $media->getHeight(),
            mime: $media->getMime(),
            alt: $media->getAlt(),
            thumbs: array_map(
                fn (Media $thumb): MediaThumbFileDTO => new MediaThumbFileDTO(
                    id: $thumb->getId(),
                    filename: basename($thumb->getPath()),
                    url: $thumb->getWebPath(),
                    mime: $thumb->getMime(),
                    width: $thumb->getWidth(),
                    height: $thumb->getHeight(),
                    sizeHumanReadable: $this->formatter->formatAsHumanReadableSize($thumb->getSize())
                ),
                $media->getThumbnails()
            )
        );
    }
}