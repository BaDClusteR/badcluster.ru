<?php

declare(strict_types=1);

namespace BC\Widget\Common\Block;

use BC\Widget\AWidget;

class Paragraph extends AWidget {
    protected function getTemplatePath(): string {
        return 'common/block/paragraph.phtml';
    }

    protected function getContent(): string {
        return (string) ($this->context['text'] ?? '');
    }

    protected function getAlignment(): string {
        $align = (string) ($this->context['alignment'] ?? '');

        return (in_array($align, ['center', 'right'], true))
            ? $align
            : '';
    }
}
