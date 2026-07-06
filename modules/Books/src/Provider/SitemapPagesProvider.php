<?php

namespace BC\Modules\Books\Provider;

use BC\DTO\SitemapEntryDTO;
use BC\Modules\Books\Model\Book;
use BC\Provider\ISitemapPagesProvider;
use Runway\Exception\Exception;

class SitemapPagesProvider implements ISitemapPagesProvider {

    /**
     * @inheritDoc
     */
    public function getSitemapPages(): array {
        $result = [
            new SitemapEntryDTO('/books')
        ];

        try {
            /** @var Book $book */
            foreach (Book::iterate() as $book) {
                $result[] = new SitemapEntryDTO($book->getUrl());

                foreach ($book->getChapters(true) as $chapter) {
                    $result[] = new SitemapEntryDTO($chapter->getUrl());
                }
            }
        } catch (Exception) {
        } finally {
            return $result;
        }
    }
}
