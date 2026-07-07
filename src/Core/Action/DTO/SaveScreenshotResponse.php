<?php

declare(strict_types=1);

namespace BC\Core\Action\DTO;

use BC\Model\Screenshot;

readonly class SaveScreenshotResponse {
    public function __construct(
        public Screenshot $screenshot
    ) {
    }
}