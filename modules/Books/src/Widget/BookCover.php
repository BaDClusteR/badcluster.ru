<?php

namespace BC\Modules\Books\Widget;

use BC\Modules\Books\Model\Book;
use BC\Widget\AWidget;

class BookCover extends AWidget {
    protected function getTemplatePath(): string {
        return 'modules/Books/cover.phtml';
    }

    protected function getBook(): Book {
        return $this->context['book'];
    }
}
