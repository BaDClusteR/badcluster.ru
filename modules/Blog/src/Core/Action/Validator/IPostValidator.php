<?php

namespace BC\Modules\Blog\Core\Action\Validator;

use BC\Modules\Blog\Core\Action\DTO\CreatePostRequest;
use BC\Modules\Blog\Core\Action\DTO\SavePostRequest;
use BC\Modules\Blog\Core\Action\DTO\ValidatorResponse;

interface IPostValidator
{
    public function validate(SavePostRequest|CreatePostRequest $request): ValidatorResponse;
}
