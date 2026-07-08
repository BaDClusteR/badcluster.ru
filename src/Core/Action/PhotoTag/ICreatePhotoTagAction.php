<?php

declare(strict_types=1);

namespace BC\Core\Action\PhotoTag;

use BC\Core\Action\DTO\CreatePhotoTagRequest;
use BC\Core\Action\DTO\CreatePhotoTagResponse;

interface ICreatePhotoTagAction {
    public function run(CreatePhotoTagRequest $request): CreatePhotoTagResponse;
}
