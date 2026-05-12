<?php

namespace BC\Api\Endpoint;

use ApiPlatform\Exception\RuntimeInternalErrorException;
use BC\Api\DTO\MediaDTO;
use BC\Api\DTO\MediaThumbnailDTO;
use BC\Model\Media;
use Throwable;

abstract class AEndpoint
{
    protected function handleWithException(callable $handler): mixed {
        try {
            return $handler();
        } catch (Throwable $e) {
            throw new RuntimeInternalErrorException($e->getMessage(), $e);
        }
    }

    protected function convertMediaModel(?Media $model): ?MediaDTO {
        if ($model === null) {
            return null;
        }

        return new MediaDTO(
            id: $model->getId(),
            url: $model->getWebPath(),
            width: $model->getWidth(),
            height: $model->getHeight(),
            mime: $model->getMime(),
            alt: $model->getAlt(),
            thumbs: array_map(
                fn(Media $thumbnail): MediaThumbnailDTO => $this->convertMediaModelThumbnail($thumbnail),
                $model->getThumbnails()
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
