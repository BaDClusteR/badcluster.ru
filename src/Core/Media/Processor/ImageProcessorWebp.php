<?php

namespace BC\Core\Media\Processor;

readonly class ImageProcessorWebp extends AImageProcessor
{
     protected function getResultImageExtension(): string
    {
        return 'webp';
    }

    protected function getSaveParameters(): string
    {
        return "Q=85";
    }

    public function getGeneratedMimeType(): string
    {
        return 'image/webp';
    }
}
