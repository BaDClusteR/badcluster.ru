<?php

namespace BC\Core\Media;

use BC\Model\Media;

interface IThumbnailGenerator
{
    /**
     * @return Media[]
     */
    public function generateThumbnails(Media $image, int $width): array;
}
