<?php

namespace BC\Modules\Blog\Core\Action\Post;

use BC\Modules\Blog\Core\Action\DTO\GetPostRequest;
use BC\Modules\Blog\Core\Action\DTO\GetPostResponse;

interface IGetPostAction
{
    public function run(GetPostRequest $request): GetPostResponse;
}
