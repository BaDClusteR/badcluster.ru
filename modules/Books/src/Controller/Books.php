<?php

declare(strict_types=1);

namespace BC\Modules\Books\Controller;

use BC\Core\Response\SuccessfulHtmlResponse;
use BC\Core\Trait\AuthTrait;
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
    use AuthTrait;

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
    public function download(string $basename, string $ext): Response {
        $format = BookFormat::findOne([
            'filename' => "$basename.$ext"
        ]);

        if ($format?->getAllowed()) {
            if ($format->getSize() === 0) {
                $format->generateDump();
            }

            return new Response(
                200,
                $format->getDump(),
                [
                    'Content-Type'        => 'application/octet-stream',
                    'Content-Disposition' => 'attachment; filename="' . $basename . '.' . $ext . '"',
                    'Content-Length'      => $format->getSize()
                ]
            );
        }

        return $this->get404Controller()->run();
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
    public function renderChapter(string $book, string $slug): Response {
        $bookModel = Book::findOne(['slug' => $book]);

        if (!$bookModel) {
            return $this->get404Controller()->run();
        }

        $conditions = [
            'book' => $bookModel,
            'slug' => $slug
        ];

        if (!$this->getAuth()->isAuthenticated()) {
            $conditions['published'] = true;
        }

        $chapter = Chapter::findOne($conditions);

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
