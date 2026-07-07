<?php

declare(strict_types=1);

namespace BC\Core\Action\Screenshot;

use BC\Core\Action\DTO\CreateScreenshotRequest;
use BC\Core\Action\DTO\CreateScreenshotResponse;

interface ICreateScreenshotAction {
    public function run(CreateScreenshotRequest $request): CreateScreenshotResponse;
}