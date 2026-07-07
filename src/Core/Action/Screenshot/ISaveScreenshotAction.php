<?php

declare(strict_types=1);

namespace BC\Core\Action\Screenshot;

use BC\Core\Action\DTO\SaveScreenshotRequest;
use BC\Core\Action\DTO\SaveScreenshotResponse;

interface ISaveScreenshotAction {
    public function run(SaveScreenshotRequest $request): SaveScreenshotResponse;
}