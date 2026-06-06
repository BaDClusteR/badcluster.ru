<?php

namespace BC\Modules\Books\Core\DTO;

readonly class BookFormatDTO {
    public function __construct(
        public string $type
    ) {
    }
}
