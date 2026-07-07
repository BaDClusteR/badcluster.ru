<?php

declare(strict_types=1);

namespace BC\Api\DataBuilder\Photo;

use BC\Api\DTO\Photo\PhotoDTO;
use BC\Api\DTO\Photo\PhotoRowDTO;
use BC\Model\Photo;

interface IPhotoDataBuilder {
    public function buildRow(Photo $photo): PhotoRowDTO;

    public function buildEntity(Photo $photo): PhotoDTO;
}