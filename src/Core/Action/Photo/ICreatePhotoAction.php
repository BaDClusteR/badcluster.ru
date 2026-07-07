<?php

declare(strict_types=1);

namespace BC\Core\Action\Photo;

use BC\Core\Action\DTO\CreatePhotoRequest;
use BC\Core\Action\DTO\CreatePhotoResponse;

interface ICreatePhotoAction {
    public function run(CreatePhotoRequest $request): CreatePhotoResponse;
}