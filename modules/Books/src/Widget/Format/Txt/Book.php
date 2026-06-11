<?php

declare(strict_types=1);

namespace BC\Modules\Books\Widget\Format\Txt;

use BC\Modules\Books\Model\Chapter;
use BC\Modules\Books\Widget\Format\ABook;

class Book extends ABook {
    protected function getTemplatePath(): string {
        return 'modules/Books/format/txt/book.phtml';
    }

    protected function getBookAnnotation(): string {
        $annotation = trim($this->getBook()->getAnnotation());

        return '  ' . str_replace("\n\n", "\n  ", $annotation);
    }

    protected function getBlockRenderer(): \BC\Widget\Blocks {
        return new Blocks();
    }

    protected function getChapterContent(Chapter $chapter): string {
        $content = $this->renderer->render([...$chapter->getContent(), 'book' => $this]);
        $contentWithNotes = $this->notesExtractor->extractNotes(
            $content,
            count($this->getNotes()) + 1,
            '[{{index}}]'
        );

        foreach ($contentWithNotes->notes as $note) {
            $this->addNote(
                strip_tags($note->content),
                $note->id
            );
        }

        return $contentWithNotes->content;
    }

    protected function getPostfix(): string {
        $template = $this->getBookFormat()?->getPostfix();

        if (!$template) {
            $template = $this->getBook()->isTranslation()
                ? "Перевел BaD ClusteR\n{{start_year}} — {{end_year}}, https://badcluster.ru"
                : "BaD ClusteR\n{{start_year}} — {{end_year}}, https://badcluster.ru";
        }

        return str_replace(
            ['{{start_year}}', '{{end_year}}'],
            [$this->getStartYear(), $this->getEndYear()],
            $template
        );
    }

    protected function getFormatType(): string {
        return 'txt';
    }
}
