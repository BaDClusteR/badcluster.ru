<?php

namespace BC\Modules\Books\Api\DTO\Book;

readonly class BookFormatsDTO {
    /**
     * @param string[] $formats
     */
    public function __construct(
        public array $formats
    ) {
    }
}
