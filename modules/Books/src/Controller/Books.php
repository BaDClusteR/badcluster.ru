<?php

declare(strict_types=1);

namespace BC\Modules\Books\Controller;

use BC\Core\Response\SuccessfulHtmlResponse;
use BC\Core\Trait\Controller404Trait;
use BC\Modules\Books\Model\Book;
use BC\Modules\Books\Model\BookFormat;
use BC\Modules\Books\Model\Chapter;
use BC\Modules\Books\Widget\Page\BookListPage;
use BC\Modules\Books\Widget\Page\BookPage;
use BC\Modules\Books\Widget\Page\ChapterPage;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Exception\Exception;
use Runway\Model\Exception\ModelException;
use Runway\Request\Response;

readonly class Books {
    use Controller404Trait;

    public function renderBookList(): Response {
        return new SuccessfulHtmlResponse(
            new BookListPage()->render(
                ['groups' => $this->getBookGroups()]
            )
        );
    }

    /**
     * @throws ModelException
     * @throws DBException
     * @throws QueryBuilderException
     */
    public function renderBook(string $book): Response {
        $bookModel = Book::findOne(['slug' => $book]);

        if (!$bookModel) {
            return $this->get404Controller()->run();
        }

        return new SuccessfulHtmlResponse(
            new BookPage(['book' => $bookModel])->render()
        );
    }

    /**
     * @throws DBException
     * @throws QueryBuilderException
     * @throws ModelException
     */
    public function renderChapterOrDownload(string $book, string $slug): Response {
        $bookModel = Book::findOne(['slug' => $book]);

        if (!$bookModel) {
            return $this->get404Controller()->run();
        }

        $format = BookFormat::findOne([
            'book'     => $book,
            'filename' => $slug
        ]);

        if ($format) {
            return new SuccessfulHtmlResponse('Here the book will be downloaded.');
        }

        $chapter = Chapter::findOne([
            'book' => $bookModel,
            'slug' => $slug
        ]);

        if (!$chapter) {
            return $this->get404Controller()->run();
        }

        return new SuccessfulHtmlResponse(
            new ChapterPage(['chapter' => $chapter])->render()
        );
    }

    /**
     * @return array<string, Book[]>
     */
    protected function getBookGroups(): array {
        $groups = [
            ''                               => [],
            'Серия «Doom» про Флая Таггарта' => [],
            'Дилогия «Doom» Мэтью Костелло'  => [],
            'Крипипаста'                     => []
        ];

        try {
            /** @var Book[] $books */
            $books = Book::find([], ['position', 'ASC']);
        } catch (Exception) {
            $books = [];
        }

        foreach ($books as $book) {
            $group = $book->getGroup();
            if ($group && array_key_exists($group, $groups)) {
                $groups[$group][] = $book;
            } else {
                $groups[''][] = $book;
            }
        }

        return $groups;
    }
}
