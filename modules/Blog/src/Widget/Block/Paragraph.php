<?php

namespace BC\Modules\Blog\Widget\Block;

use BC\Widget\AWidget;

class Paragraph extends AWidget
{
    protected function getTemplatePath(): string {
        return 'modules/Blog/Block/paragraph.phtml';
    }

    protected function getContent(): string {
        return (string)($this->context['text'] ?? '');
    }
}
