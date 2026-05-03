<?php

namespace BC\Api\DTO;

readonly class BlogPostTagsDTO
{
    /**
     * @param BlogPostTagDTO[] $tags
     */
    public function __construct(
        public array $tags,
    ) {
    }
}
