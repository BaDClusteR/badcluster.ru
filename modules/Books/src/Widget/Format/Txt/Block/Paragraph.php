<?php

declare(strict_types=1);

namespace BC\Modules\Books\Widget\Format\Txt\Block;

use BC\Core\Helper\MixedValue;
use BC\Widget\AWidget;

class Paragraph extends AWidget {
    protected function getTemplatePath(): string {
        return 'modules/Books/format/txt/block/paragraph.phtml';
    }

    protected function getContent(): string {
        return $this->prepareContent(
            MixedValue::toString($this->context['text'] ?? '')
        );
    }

    private function prepareContent(string $content): string {
        $content = str_replace(
            ['<br>', '<br />'],
            "\n",
            $content
        );

        $content = strip_tags($content, ['sup']);

        // В тексте из редактора бывают сущности (&nbsp;, &gt; и т.п.) — в TXT
        // они нужны символами, а неразрывные пробелы — обычными.
        $content = trim(
            str_replace(
                "\u{00A0}",
                ' ',
                html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8')
            )
        );

        return $content
            ? "  $content"
            : '';
    }
}
