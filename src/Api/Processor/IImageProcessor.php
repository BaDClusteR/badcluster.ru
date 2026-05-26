<?php

namespace BC\Api\Processor;

use BC\Model\Media;

interface IImageProcessor {
    public function process(Media $image, ?string $purpose): Media;
}
