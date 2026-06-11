<?php

namespace BC\Modules\Books\Format;

use BC\Modules\Books\Model\Book;

class Fb2 implements IBookFormat {
    public function getType(): string {
        return 'fb2';
    }

    public function generateBook(Book $book): string {
        return new \BC\Modules\Books\Widget\Format\Fb2\Book()->render(['book' => $book]);
    }
}
