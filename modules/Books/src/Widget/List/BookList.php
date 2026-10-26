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
        $groups = [];

        foreach ((array) ($this->context['groups'] ?? []) as $title => $books) {
            if (is_array($books)) {
                $groups[(string) $title] = array_filter(
                    $books,
                    static fn (mixed $book): bool => $book instanceof Book
                );
            }
        }

        return $groups;
    }
}
