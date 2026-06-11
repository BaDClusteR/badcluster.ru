<?php

namespace BC\Modules\Books\Api\DTO\Book;

use BC\Core\DTO\MediaDTO;

readonly class BookDTO {
    /**
     * @param BookFormatDTO[] $formats
     */
    public function __construct(
        public string $slug,
        public ?MediaDTO $cover,
        public ?MediaDTO $coverBg,
        public string $title,
        public ?string $author,
        public string $annotation,
        public string $shortAnnotation,
        public string $type,
        public string $lastUpdateDate,
        public string $group,
        public int $position,
        public string $fb2Genre,
        public array $formats
    ) {
    }
}
