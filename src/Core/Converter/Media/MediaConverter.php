<?php

declare(strict_types=1);

namespace BC\Core\Converter\Media;

use BC\Core\DTO\MediaDTO;
use BC\Core\DTO\MediaThumbnailDTO;
use BC\Model\Media;

class MediaConverter implements IMediaConverter {
    public function convertMedia(?Media $media): ?MediaDTO {
        if ($media === null) {
            return null;
        }

        return new MediaDTO(
            id: $media->getId(),
            url: $media->getWebPath(),
            width: $media->getWidth(),
            height: $media->getHeight(),
            mime: $media->getMime(),
            alt: $media->getAlt(),
            thumbs: array_map(
                fn (Media $thumbnail): MediaThumbnailDTO => $this->convertMediaModelThumbnail($thumbnail),
                $media->getThumbnails()
            )
        );
    }

    protected function convertMediaModelThumbnail(Media $thumbnail): MediaThumbnailDTO {
        return new MediaThumbnailDTO(
            id: $thumbnail->getId(),
            width: $thumbnail->getWidth(),
            height: $thumbnail->getHeight(),
            mime: $thumbnail->getMime(),
            url: $thumbnail->getWebPath()
        );
    }
}
