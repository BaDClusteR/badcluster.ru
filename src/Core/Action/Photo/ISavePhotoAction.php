<?php

declare(strict_types=1);

namespace BC\Core\Action\Photo;

use BC\Core\Action\DTO\SavePhotoRequest;
use BC\Core\Action\DTO\SavePhotoResponse;

interface ISavePhotoAction {
    public function run(SavePhotoRequest $request): SavePhotoResponse;
}