<?php

namespace BC\Modules\Books\Format;

use BC\Modules\Books\Model\Book;

class Txt implements IBookFormat {
    public function getType(): string {
        return 'txt';
    }

    public function generateBook(Book $book): string {
        return new \BC\Modules\Books\Widget\Format\Txt\Book()->render(['book' => $book]);
    }
}
