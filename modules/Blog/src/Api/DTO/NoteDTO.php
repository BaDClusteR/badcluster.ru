<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Api\DTO;

readonly class NoteDTO {
    public function __construct(
        public int $id,
        public string $title,
        public string $metaDescription,
        public string $publishDate,
        public array $content,
        public bool $published,
        public string $slug
    ) {
    }
}
