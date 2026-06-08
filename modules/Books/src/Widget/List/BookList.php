<?php

namespace BC\Modules\Books\Widget\List;

use BC\Modules\Books\Model\Book;
use BC\Widget\AWidget;

class BookList extends AWidget {
    protected function getTemplatePath(): string {
        return 'modules/Books/list/body.phtml';
    }

    /**
     * @return array<string, Book[]>
     */
    protected function getBookGroups(): array {
        return (array) ($this->context['groups'] ?? []);
    }
}
