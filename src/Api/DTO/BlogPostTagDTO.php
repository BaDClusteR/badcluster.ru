<?php

namespace BC\Api\DTO;

readonly class BlogPostTagDTO
{
    public function __construct(
        public int $id,
        public string $title
    ) {
    }
}
