<?php

declare(strict_types=1);

namespace BC\Modules\Books\Widget;

use BC\Core\Trait\AuthTrait;
use BC\Modules\Books\Core\DTO\ExtractedNotesWithContentDTO;
use BC\Modules\Books\Core\Extractor\INotesExtractor;
use BC\Modules\Books\Model\Book as BookModel;
use BC\Modules\Books\Model\Chapter as ChapterModel;
use BC\Widget\AWidget;
use Runway\Exception\Exception;
use Runway\Singleton\Container;

class Chapter extends AWidget {
    use AuthTrait;

    protected function getTemplatePath(): string {
        return 'modules/Books/chapter.phtml';
    }

    protected function getChapter(): ChapterModel {
        return $this->context['chapter'];
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
            $allChapters = $book->getChapters(
                !$this->getAuth()->isAuthenticated()
            );
        } catch (Exception) {
            $allChapters = [];
        }

        foreach ($allChapters as $i => $_chapter) {
            if ($_chapter->getId() === $chapter->getId()) {
                if ($i > 0) {
                    $result['prev'] = [$allChapters[$i - 1]->getUrl(), $allChapters[$i - 1]->getPublicTitle()];
                }

                if ($i < count($allChapters) - 1) {
                    $result['next'] = [$allChapters[$i + 1]->getUrl(), $allChapters[$i + 1]->getPublicTitle()];
                }
            }
        }

        return $result;
    }

    protected function extractNotes(string $content): ExtractedNotesWithContentDTO {
        return Container::getInstance()->getService(INotesExtractor::class)->extractNotes($content);
    }
}
