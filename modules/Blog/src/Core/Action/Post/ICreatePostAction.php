<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Core\Action\Post;

use BC\Modules\Blog\Core\Action\DTO\CreatePostRequest;
use BC\Modules\Blog\Core\Action\DTO\CreatePostResponse;
use Runway\Exception\Exception;

interface ICreatePostAction {
    /**
     * @throws Exception
     */
    public function run(CreatePostRequest $request): CreatePostResponse;
}
