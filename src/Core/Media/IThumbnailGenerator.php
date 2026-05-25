<?php

declare(strict_types=1);

namespace BC\Core\Media;

use BC\Model\Media;

interface IThumbnailGenerator {
    /**
     * @return Media[]
     */
    public function generateThumbnails(Media $image, int $width, bool $force): array;
}
