<?php

namespace BC\Modules\Music\Api\DataBuilder\Album;

use BC\Modules\Music\Api\DTO\Album\AlbumDTO;
use BC\Modules\Music\Api\DTO\Album\AlbumRowDTO;
use BC\Modules\Music\Model\Album;

interface IAlbumDataBuilder {
    public function buildRow(Album $album): AlbumRowDTO;

    public function buildEntity(Album $album): AlbumDTO;
}
