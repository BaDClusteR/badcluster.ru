<?php

declare(strict_types=1);

namespace BC\Modules\Books\Widget\Format\Fb2\Block;

use BC\Widget\AWidget;
use Dom\Element;
use Dom\HTMLDocument;
use Dom\Node;
use Dom\Text;

class Paragraph extends AWidget {
    /**
     * HTML-тэги редактора → inline-тэги FB2. Всё, чего здесь нет (span и прочее),
     * разворачивается: остаётся только содержимое.
     */
    private const array TAG_MAP = [
        'b' => 'strong',
        'strong' => 'strong',
        'i' => 'emphasis',
        'em' => 'emphasis',
        'code' => 'code',
        'kbd' => 'code',
        's' => 'strikethrough',
        'del' => 'strikethrough',
        'strike' => 'strikethrough',
        'sub' => 'sub',
        'sup' => 'sup',
        'a' => 'a',
    ];

    protected function getTemplatePath(): string {
        return 'modules/Books/format/fb2/block/paragraph.phtml';
    }

    /**
     * Строки абзаца: каждая — содержимое отдельного <p>, пустая строка — <empty-line />.
     *
     * @return list<string>
     */
    protected function getLines(): array {
        return $this->splitLines(
            (string) ($this->context['text'] ?? '')
        );
    }

    /**
     * Абзацы по центру по смыслу — подзаголовки. Остальное выравнивание в книге не нужно.
     */
    protected function getTag(): string {
        return ($this->context['alignment'] ?? '') === 'center'
            ? 'subtitle'
            : 'p';
    }

    /**
     * В FB2 нет <br>, поэтому абзац с переносами режется на несколько <p>.
     * На каждом <br> все открытые в этот момент тэги закрываются в текущей строке
     * и переоткрываются в следующей, так что каждая строка — корректный XML.
     *
     * @return list<string>
     */
    private function splitLines(string $html): array {
        $document = HTMLDocument::createFromString(
            '<!DOCTYPE html><html><body>' . $html . '</body></html>',
            LIBXML_NOERROR
        );

        $lines = [''];
        $this->walk($document->body, [], $lines);

        $lines = array_map($this->cleanLine(...), $lines);

        // Переносы в начале и в конце абзаца ничего не значат,
        // а в середине превращаются в <empty-line />.
        while ($lines && reset($lines) === '') {
            array_shift($lines);
        }

        while ($lines && end($lines) === '') {
            array_pop($lines);
        }

        return $lines;
    }

    /**
     * @param list<array{name: string, open: string}> $stack открытые FB2-тэги
     * @param non-empty-list<string> $lines
     */
    private function walk(Node $parent, array $stack, array &$lines): void {
        foreach ($parent->childNodes as $node) {
            if ($node instanceof Text) {
                $lines[array_key_last($lines)] .= $this->escape(
                    str_replace("\u{00A0}", ' ', $node->textContent)
                );

                continue;
            }

            if (!$node instanceof Element) {
                continue;
            }

            if ($node->localName === 'br') {
                foreach (array_reverse($stack) as $tag) {
                    $lines[array_key_last($lines)] .= '</' . $tag['name'] . '>';
                }

                $lines[] = implode('', array_column($stack, 'open'));

                continue;
            }

            $tag = $this->convertTag($node);

            if ($tag === null) {
                $this->walk($node, $stack, $lines);

                continue;
            }

            $lines[array_key_last($lines)] .= $tag['open'];
            $this->walk($node, [...$stack, $tag], $lines);
            $lines[array_key_last($lines)] .= '</' . $tag['name'] . '>';
        }
    }

    /**
     * @return array{name: string, open: string}|null
     */
    private function convertTag(Element $element): ?array {
        $name = self::TAG_MAP[$element->localName] ?? null;

        if ($name === null) {
            return null;
        }

        if ($name !== 'a') {
            return ['name' => $name, 'open' => "<$name>"];
        }

        $href = trim((string) $element->getAttribute('href'));

        return $href !== ''
            ? ['name' => 'a', 'open' => '<a l:href="' . $this->escape($href) . '">']
            : null;
    }

    private function cleanLine(string $line): string {
        // Пустые пары вроде <strong></strong> (или с одними пробелами внутри) остаются
        // после переносов на границе тэга — разворачиваем их, оставляя пробелы.
        do {
            $line = preg_replace('/<([\w:]+)(?:\s[^>]*)?>(\s*)<\/\1>/u', '$2', $line, -1, $count);
        } while ($count > 0);

        $line = trim($line);

        // Строка из одних пробелов, табуляций и прочих невидимых символов — пустая.
        return preg_match('/^[\s\p{Z}\x{200B}\x{FEFF}]*$/u', strip_tags($line))
            ? ''
            : $line;
    }

    private function escape(string $text): string {
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
