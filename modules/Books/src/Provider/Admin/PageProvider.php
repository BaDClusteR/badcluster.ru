<?php

declare(strict_types=1);

namespace BC\Modules\Books\Provider\Admin;

use BC\DTO\PageDTO;
use BC\Modules\Books\Model\Book;
use BC\Provider\Admin\IPageProvider;
use Runway\Exception\Exception;

readonly class PageProvider implements IPageProvider {
    public function __construct(
        private IPageProvider $inner
    ) {
    }

    public function getPage(string $pageType, int $pageId): PageDTO {
        if ($pageType === 'book') {
            try {
                $book = Book::findByUniqueIdentifier($pageId);

                return $book
                    ? new PageDTO(
                        $book->getTitle(),
                        "/admin/books/{$book->getId()}"
                    )
                    : new PageDTO('Неизвестное произведение', '');
            } catch (Exception) {
                return new PageDTO('Неизвестное произведение', '');
            }
        }

        return $this->inner->getPage($pageType, $pageId);
    }
}
