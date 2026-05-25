<?php

declare(strict_types=1);

namespace BC\Core\Converter\Media;

use BC\Core\DTO\MediaDTO;
use BC\Model\Media;

interface IMediaConverter {
    public function convertMedia(?Media $media): ?MediaDTO;
}
