<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Api\DTO;

readonly class BlogPostTagsDTO {
    /**
     * @param BlogPostTagDTO[] $tags
     */
    public function __construct(
        public array $tags,
    ) {
    }
}
