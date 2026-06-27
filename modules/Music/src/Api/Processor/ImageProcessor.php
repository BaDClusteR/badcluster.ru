<?php

namespace BC\Modules\Music\Api\Processor;

use BC\Api\Processor\IImageProcessor;
use BC\Model\Media;

readonly class ImageProcessor implements IImageProcessor {
    public function __construct(
        private IImageProcessor $inner
    ) {
    }

    public function process(Media $image, ?string $purpose): Media {
        if ($purpose === 'album_cover') {
            $image->tryGenerateThumbnails([240, 480]);

            return $image;
        }

        return $this->inner->process($image, $purpose);
    }
}
