<?php

namespace BC\Api\DTO;

class BlogPostDTO
{
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public bool $published,
        public string $publishDate
    ) {
    }
}
