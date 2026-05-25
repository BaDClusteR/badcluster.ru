<?php

declare(strict_types=1);

namespace BC\Core\Media\PostProcessor;

use BC\Model\Media;

interface IImagePostprocessor {
    /**
     * @param Media[][] $thumbnailGroups
     *
     * @return Media[][]
     */
    public function postProcessThumbnails(array $thumbnailGroups): array;
}
