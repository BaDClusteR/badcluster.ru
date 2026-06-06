<?php

declare(strict_types=1);

namespace BC\Modules\Books\Core\Media\Processor;

use BC\Core\Media\Processor\AImageProcessor;

/**
 * DO NOT tag it as image.processor! Otherwise, all other images will get redundant JPG thumbnails.
 */
readonly class ImageProcessorJpg extends AImageProcessor {

    protected function getResultImageExtension(): string {
        return 'jpg';
    }

    protected function getSaveParameters(): string {
        return '';
    }

    public function getGeneratedMimeType(): string {
        return 'image/jpeg';
    }
}
