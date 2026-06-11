<?php

declare(strict_types=1);

namespace BC\Modules\Books\Widget\Format\Fb2\Block;

use BC\Widget\AWidget;

class Paragraph extends AWidget {
    protected function getTemplatePath(): string {
        return 'modules/Books/format/fb2/block/paragraph.phtml';
    }

    protected function getContent(): string {
        return $this->prepareContent(
            (string) ($this->context['text'] ?? '')
        );
    }

    /** @noinspection CascadeStringReplacementInspection */
    private function prepareContent(string $content): string {
        $content = str_replace(
            ['<br>', '<br />'],
            '</p><p>',
            $content
        );

        if (str_starts_with($content, '</p>')) {
            $content = substr($content, strlen('</p>'));
        }

        if (str_ends_with($content, '<p>')) {
            $content = substr($content, 0, -strlen('<p>'));
        }

        $content = preg_replace('/\s*style="(.*)"/', '', $content);

        $content = str_replace(
            ['<i>', '</i>', '<em>', '</em>', '<b>', '</b>', '<kbd>', '</kbd>'],
            ['<emphasis>', '</emphasis>', '<emphasis>', '</emphasis>', '<strong>', '</strong>', '<code>', '</code>'],
            $content
        );

        $content = str_replace(
            ['href=', 'target="_blank"'],
            ['l:href=', ''],
            $content
        );

        $content = preg_replace(
            '/<emphasis>(.*?)<\/p>/',
            '<emphasis>$1</emphasis></p>',
            $content
        );

        $content = preg_replace(
            '/<strong>(.*?)<\/p>/',
            '<strong>$1</strong></p>',
            $content
        );

        $content = preg_replace(
            '/<code>(.*?)<\/p>/',
            '<code>$1</code></p>',
            $content
        );

        $content = str_replace(
            [
                '</emphasis></emphasis></p>',
                '</strong></strong></p>',
                '</code></code></p>'
            ],
            [
                '</emphasis></p>',
                '</strong></p>',
                '</code></p>'
            ],
            $content
        );

        return str_replace(
            '&nbsp;',
            ' ',
            $content
        );
    }
}
