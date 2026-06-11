<?php

namespace BC\Modules\Books\Format;

use BC\Modules\Books\Model\Book;

interface IBookFormat {
    public function getType(): string;

    public function generateBook(Book $book): string;
}
