<?php

namespace BC\Modules\Books\Api\DTO\Book;

use BC\Core\DTO\MediaDTO;
use DateTime;

readonly class BookRowDTO {
    public function __construct(
        public int $id,
        public ?MediaDTO $cover,
        public string $title,
        public string $shortAnnotation,
        public string $type,
        public string $lastUpdateDate
    ) {
    }
}
