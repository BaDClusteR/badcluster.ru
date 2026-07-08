<?php

declare(strict_types=1);

namespace BC\Api\DataBuilder\PhotoTag;

use BC\Api\DTO\PhotoTag\PhotoTagDTO;
use BC\Api\DTO\PhotoTag\PhotoTagRowDTO;
use BC\Model\PhotoTag;

interface IPhotoTagDataBuilder {
    public function buildRow(PhotoTag $tag, int $photosCount): PhotoTagRowDTO;

    public function buildEntity(PhotoTag $tag): PhotoTagDTO;
}
