<?php

declare(strict_types=1);

namespace BC\Api\DataBuilder\Media;

use BC\Api\DTO\Media\MediaDetailsDTO;
use BC\Api\DTO\Media\MediaRowDTO;
use BC\Model\Media;

interface IMediaDataBuilder {
    public function buildRow(Media $media): MediaRowDTO;

    public function buildEntity(Media $media): MediaDetailsDTO;
}