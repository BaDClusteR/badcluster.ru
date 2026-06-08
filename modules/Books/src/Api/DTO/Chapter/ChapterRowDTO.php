<?php

namespace BC\Modules\Books\Api\DTO\Chapter;

readonly class ChapterRowDTO {
    public function __construct(
        public int $id,
        public string $title,
        public string $addedDate,
        public string $updateDate,
        public bool $published
    ) {
    }
}
