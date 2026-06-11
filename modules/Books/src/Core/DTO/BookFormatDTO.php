<?php

namespace BC\Modules\Books\Core\DTO;

use BC\Modules\Books\Format\IBookFormat;

readonly class BookFormatDTO {
    public function __construct(
        public string $type,
        public IBookFormat $format
    ) {
    }
}
