<?php

declare(strict_types=1);

namespace BC\Modules\Books\Api\DTO\Book;

readonly class BookFormatGenerateDTO {
    public function __construct(
        public string $date,
        public int $size,
        public string $sizeHumanReadable
    ) {
    }
}
