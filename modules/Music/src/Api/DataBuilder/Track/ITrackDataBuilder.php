<?php

namespace BC\Modules\Music\Api\DataBuilder\Track;

use BC\Modules\Music\Api\DTO\Track\TrackDTO;
use BC\Modules\Music\Api\DTO\Track\TrackRowDTO;
use BC\Modules\Music\Model\Track;

interface ITrackDataBuilder {
    public function buildRow(Track $track): TrackRowDTO;

    public function buildEntity(Track $track): TrackDTO;
}
