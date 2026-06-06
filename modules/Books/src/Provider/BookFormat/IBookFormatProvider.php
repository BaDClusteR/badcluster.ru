<?php

namespace BC\Modules\Books\Provider\BookFormat;

use BC\Modules\Books\Core\DTO\BookFormatDTO;

interface IBookFormatProvider {
    /**
     * @return BookFormatDTO[]
     */
    public function getFormats(): array;
}
