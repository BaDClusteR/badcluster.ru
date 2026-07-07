<?php

declare(strict_types=1);

namespace BC\Api\DataBuilder\Screenshot;

use BC\Api\DTO\Screenshot\ScreenshotDTO;
use BC\Api\DTO\Screenshot\ScreenshotRowDTO;
use BC\Model\Screenshot;

interface IScreenshotDataBuilder {
    public function buildRow(Screenshot $screenshot): ScreenshotRowDTO;

    public function buildEntity(Screenshot $screenshot): ScreenshotDTO;
}