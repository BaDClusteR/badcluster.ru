<?php

namespace BC\Core\Converter\Media;

use BC\Core\DTO\MediaDTO;
use BC\Model\Media;

interface IMediaConverter {
    public function convertMedia(?Media $media): ?MediaDTO;
}
