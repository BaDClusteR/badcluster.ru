<?php

namespace BC\Core\Media\Processor;

use BC\Core\DTO\ImageDTO;
use BC\Core\Exception\ImageException;

interface IImageProcessor
{
    /**
     * @throws ImageException
     */
    public function getThumbnail(string $path, int $width, int $height): ImageDTO;

    public function isApplicable(string $path): bool;
}
