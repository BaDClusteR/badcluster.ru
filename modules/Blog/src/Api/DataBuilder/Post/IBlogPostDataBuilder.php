<?php

namespace BC\Modules\Blog\Api\DataBuilder\Post;

use BC\Modules\Blog\Api\DTO\BlogPostDTO;
use BC\Modules\Blog\Api\DTO\BlogPostRowDTO;
use BC\Modules\Blog\Model\Post;

interface IBlogPostDataBuilder {
    public function buildRow(Post $post): BlogPostRowDTO;

    public function buildEntity(Post $post): BlogPostDTO;
}
