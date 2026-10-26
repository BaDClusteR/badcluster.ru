<?php

namespace BC\Modules\Books\Widget;

use BC\Modules\Books\Model\Book;
use BC\Widget\AWidget;
use Runway\Exception\RuntimeException;

class BookCover extends AWidget {
    protected function getTemplatePath(): string {
        return 'modules/Books/cover.phtml';
    }

    protected function getBook(): Book {
        $book = $this->context['book'] ?? null;

        return ($book instanceof Book)
            ? $book
            : throw new RuntimeException('The widget needs a book');
    }
}
