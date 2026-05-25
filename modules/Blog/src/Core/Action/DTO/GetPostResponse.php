<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Core\Action\DTO;

use BC\Modules\Blog\Model\Post;

readonly class GetPostResponse {
    public function __construct(
        public Post $post
    ) {
    }
}
