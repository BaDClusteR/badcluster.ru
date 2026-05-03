<?php

namespace BC\Api\DTO;

use DateTime;

readonly class BlogPostDetailedDTO
{
    public function __construct(
        public int $id,
        public string $title,
        public string $createdDate,
        public string $publishDate,
        public ?string $updateDate,
        public array $content,
        public bool $published,
        public string $slug
    ) {
    }
}
