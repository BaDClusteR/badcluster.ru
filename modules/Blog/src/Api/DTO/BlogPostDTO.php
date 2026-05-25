<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Api\DTO;

readonly class BlogPostDTO {
    /**
     * @param string[] $tags
     */
    public function __construct(
        public int $id,
        public string $title,
        public string $shortTitle,
        public string $metaDescription,
        public string $annotation,
        public ?array $coverImage,
        public string $publishDate,
        public ?string $updateDate,
        public array $content,
        public bool $published,
        public string $slug,
        public array $tags
    ) {
    }
}
