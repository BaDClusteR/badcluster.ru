<?php

declare(strict_types=1);

namespace BC\Modules\Books\Api\DTO\Chapter;

readonly class ChapterDTO {
    public function __construct(
        public string $title,
        public array $content,
        public int $position,
        public bool $published,
        public string $addedDate,
        public string $slug,
        public string $part
    ) {
    }
}
