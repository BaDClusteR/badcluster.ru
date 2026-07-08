<?php

declare(strict_types=1);

namespace BC\Core\Action\PhotoTag;

use BC\Core\Action\DTO\SavePhotoTagRequest;

interface ISavePhotoTagAction {
    public function run(SavePhotoTagRequest $request): void;
}
