<?php

declare(strict_types=1);

namespace BC\Core\Action\DTO;

use BC\Model\Screenshot;

readonly class CreateScreenshotResponse {
    public function __construct(
        public Screenshot $screenshot
    ) {
    }
}