<?php

namespace BC\Api\Processor;

use BC\Model\Media;

readonly class ImageProcessor implements IImageProcessor {
    public function process(Media $image, ?string $purpose): Media {
        if ($purpose === null) {
            $image->tryGenerateThumbnails([500, 1000, 2000]);
        }

        return $image;
    }
}
