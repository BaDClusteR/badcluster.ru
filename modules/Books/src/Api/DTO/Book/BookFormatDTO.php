<?php

namespace BC\Modules\Books\Api\DTO\Book;

readonly class BookFormatDTO {
    public function __construct(
        public int $id,
        public string $type,
        public bool $allowed,
        public string $filename,
        public int $size,
        public string $sizeHumanReadable,
        public string $dateGenerated,
        public string $postfix
    ) {
    }
}
