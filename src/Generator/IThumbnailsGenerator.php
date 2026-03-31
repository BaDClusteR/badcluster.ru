<?php

declare(strict_types=1);

namespace BC\Generator;

use BC\Model\Media;

interface IThumbnailsGenerator
{
    /**
     * @param int[] $widths
     *
     * @return Media[][]
     */
    public function generateThumbnails(Media $image, array $widths): array;
}
