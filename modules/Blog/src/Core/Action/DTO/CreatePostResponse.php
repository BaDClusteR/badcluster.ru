<?php

namespace BC\Modules\Blog\Core\Action\DTO;

use BC\Modules\Blog\Model\Post;

readonly class CreatePostResponse
{
    public function __construct(
        public Post $post
    ) {
    }
}
