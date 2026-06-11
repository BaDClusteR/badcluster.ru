<?php

namespace BC\Modules\Books\Widget\Format\Fb2;

use BC\Modules\Books\Widget\Format\Fb2\Block\Media;
use BC\Modules\Books\Widget\Format\Fb2\Block\Paragraph;

class Blocks extends \BC\Widget\Blocks {
    protected function renderBlock(string $type, array $data): string {
        $context = [...$data, 'book' => $this->getBook()];

        $widget = match ($type) {
            'paragraph' => new Paragraph($context),
            'media' => new Media($context),
            default => null
        };

        return (string) $widget?->render();
    }

    protected function getBook(): Book {
        return $this->context['book'];
    }
}
