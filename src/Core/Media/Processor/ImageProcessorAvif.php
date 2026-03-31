<?php

namespace BC\Core\Media\Processor;

readonly class ImageProcessorAvif extends AImageProcessor
{
     protected function getResultImageExtension(): string
    {
        return 'avif';
    }

    protected function getSaveParameters(): string
    {
        return "Q=75,effort=6";
    }
}
