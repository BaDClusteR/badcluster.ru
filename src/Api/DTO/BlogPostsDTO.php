<?php

declare(strict_types=1);

namespace BC\Api\DTO;

use ApiPlatform\Attribute\Docs;

class BlogPostsDTO
{
    public function __construct(
        #[Docs\Property(description: "Blog posts", childrenType: BlogPostDTO::class)]
        public array $posts
    ) {}
}
