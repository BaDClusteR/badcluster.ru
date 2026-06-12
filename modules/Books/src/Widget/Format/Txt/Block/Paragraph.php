<?php

declare(strict_types=1);

namespace BC\Modules\Books\Widget\Format\Txt\Block;

use BC\Widget\AWidget;

class Paragraph extends AWidget {
    protected function getTemplatePath(): string {
        return 'modules/Books/format/txt/block/paragraph.phtml';
    }

    protected function getContent(): string {
        return $this->prepareContent(
            (string) ($this->context['text'] ?? '')
        );
    }

    private function prepareContent(string $content): string {
        $content = str_replace(
            ['<br>', '<br />'],
            "\n",
            $content
        );

        $content = strip_tags($content, ['sup']);

        $content = trim(
            str_replace(
                '&nbsp;',
                ' ',
                $content
            )
        );

        return $content
            ? "  $content"
            : '';
    }
}
