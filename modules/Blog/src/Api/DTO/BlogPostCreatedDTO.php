<?php

namespace BC\Modules\Blog\Api\DTO;

readonly class BlogPostCreatedDTO
{
    public function __construct(
        public int $id
    ) {
    }
}
