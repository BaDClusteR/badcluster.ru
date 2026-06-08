<?php

declare(strict_types=1);

namespace BC\Modules\Books\Widget;

use BC\Modules\Books\Model\Book as BookModel;
use BC\Modules\Books\Model\Chapter as ChapterModel;
use BC\Widget\AWidget;
use Runway\Exception\Exception;

class Chapter extends AWidget {
    protected function getTemplatePath(): string {
        return 'modules/Books/chapter.phtml';
    }

    protected function getChapter(): ChapterModel {
        return $this->context['chapter'];
    }

    protected function getRefs(): array {
        return ['n1' => 'Тестовая сноска'];
    }

    private function getBook(): BookModel {
        return $this->getChapter()->getBook();
    }

    protected function getNavLinks(): array {
        $book = $this->getBook();
        $chapter = $this->getChapter();

        $result = [
            'prev' => null,
            'toc'  => [
                $book->getUrl(),
                'Оглавление'
            ],
            'next' => null
        ];

        try {
            $allChapters = $book->getChapters();
        } catch (Exception) {
            $allChapters = [];
        }

        foreach ($allChapters as $i => $_chapter) {
            if ($_chapter->getId() === $chapter->getId()) {
                if ($i > 0) {
                    $result['prev'] = [$allChapters[$i - 1]->getUrl(), $allChapters[$i - 1]->getTitle()];
                }

                if ($i < count($allChapters) - 1) {
                    $result['next'] = [$allChapters[$i + 1]->getUrl(), $allChapters[$i + 1]->getTitle()];
                }
            }
        }

        return $result;
    }
}
