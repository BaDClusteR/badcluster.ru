<?php

namespace BC\Modules\Books\Widget\Format\Txt;

use BC\Modules\Books\Widget\Format\Txt\Block\Delimiter;
use BC\Modules\Books\Widget\Format\Txt\Block\Media;
use BC\Modules\Books\Widget\Format\Txt\Block\Paragraph;
use Runway\Exception\RuntimeException;

class Blocks extends \BC\Widget\Blocks {
    /**
     * @param array<string, mixed> $data
     */
    protected function renderBlock(string $type, array $data): string {
        $context = [...$data, 'book' => $this->getBook()];

        $widget = match ($type) {
            'paragraph' => new Paragraph($context),
            'media' => new Media($context),
            'delimiter' => new Delimiter($context),
            default => null
        };

        return (string) $widget?->render();
    }

    protected function getBook(): Book {
        $book = $this->context['book'] ?? null;

        return ($book instanceof Book)
            ? $book
            : throw new RuntimeException('The widget needs a book');
    }

    protected function getTemplatePath(): string {
        return 'modules/Books/format/txt/blocks.phtml';
    }
}
