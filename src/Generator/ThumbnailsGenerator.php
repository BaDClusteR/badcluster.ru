<?php

namespace BC\Generator;

use BC\Core\Media\IThumbnailGenerator;
use BC\Core\Media\PostProcessor\IImagePostprocessor;
use BC\Model\Media;
use Runway\Singleton\Container;

readonly class ThumbnailsGenerator implements IThumbnailsGenerator
{
    public function __construct(
        private IThumbnailGenerator $thumbnailGenerator
    ) {
    }

    /**
     * @inheritDoc
     */
    public function generateThumbnails(Media $image, array $widths): array
    {
        $result = [];

        foreach ($widths as $width) {
            $result[] = $this->thumbnailGenerator->generateThumbnails($image, $width);
        }

        foreach ($this->getImagePostprocessors() as $postprocessor) {
            $result = $postprocessor->postProcessThumbnails($result);
        }

        return $result;
    }

    /**
     * @return IImagePostprocessor[]
     */
    private function getImagePostprocessors(): array {
        return Container::getInstance()->getServicesByTag('image.postprocessor');
    }
}
