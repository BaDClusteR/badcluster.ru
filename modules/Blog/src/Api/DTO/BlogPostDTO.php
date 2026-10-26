<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Api\DTO;

readonly class BlogPostDTO {
    /**
     * @param array<string, mixed>|null $coverImage The media (MediaDTO::toArray()).
     * @param array<string, mixed>      $content    The content of the editor.
     * @param string[]                  $tags       The ids of the tags.
     */
    public function __construct(
        public int $id,
        public string $title,
        public string $shortTitle,
        public string $subtitle,
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
