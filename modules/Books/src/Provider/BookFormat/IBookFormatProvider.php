<?php

namespace BC\Modules\Books\Provider\BookFormat;

use BC\Modules\Books\Core\DTO\BookFormatDTO;
use BC\Modules\Books\Format\IBookFormat;

interface IBookFormatProvider {
    /**
     * @return BookFormatDTO[]
     */
    public function getFormats(): array;

    public function getFormat(string $type): ?IBookFormat;
}
