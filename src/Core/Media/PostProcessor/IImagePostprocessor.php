<?php

namespace BC\Core\Media\PostProcessor;

use BC\Model\Media;

interface IImagePostprocessor
{
    /**
     * @param Media[][] $thumbnailGroups
     *
     * @return Media[][]
     */
    public function postProcessThumbnails(array $thumbnailGroups): array;
}
