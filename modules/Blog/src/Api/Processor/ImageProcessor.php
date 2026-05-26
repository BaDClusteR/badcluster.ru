<?php

namespace BC\Modules\Blog\Api\Processor;

use BC\Api\Processor\IImageProcessor;
use BC\Model\Media;

readonly class ImageProcessor implements IImageProcessor {
    public function __construct(
        private IImageProcessor $inner
    ) {
    }

    public function process(Media $image, ?string $purpose): Media {
        if ($purpose === 'cover') {
            $image->tryGenerateThumbnails([200]);

            return $image;
        }

        return $this->inner->process($image, $purpose);
    }
}
