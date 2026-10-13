<?php

declare(strict_types=1);

namespace BC\Core\Typography;

class Typographer implements ITypographer {
    private const string NBSP = "\u{00A0}";

    /**
     * Внутри этих тэгов текст не трогаем: там код и азбука Морзе.
     */
    private const array RAW_TAGS = ['kbd', 'code', 'pre'];

    /**
     * Открывающие тэги, с которых начинается новая строка (абзацы в цитатах и т.п.).
     */
    private const array LINE_TAGS = ['p', 'li', 'div', 'blockquote'];

    /**
     * Короткие английские слова, после которых ставится неразрывный пробел
     * (в любом регистре; «I» — отдельно, только заглавная).
     */
    private const string EN_SHORT_WORDS = '(?i:a|an|the|at|by|in|of|on|to|for|as|and|or|but|nor)';

    public function process(string $html): string {
        $parts = preg_split('/(<[^>]*>)/u', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($parts === false) {
            return $html;
        }

        $rawDepth = 0;
        // Начало абзаца или новой строки: тире здесь открывает реплику.
        $lineStart = true;

        foreach ($parts as $i => $part) {
            if ($i % 2 === 1) {
                if (preg_match('~^<(/?)([a-z0-9]+)~i', $part, $matches)) {
                    $tag = strtolower($matches[2]);

                    if (in_array($tag, self::RAW_TAGS, true)) {
                        $rawDepth = max(0, $rawDepth + ($matches[1] === '/' ? -1 : 1));
                    } elseif (
                        $tag === 'br'
                        || ($matches[1] === '' && in_array($tag, self::LINE_TAGS, true))
                    ) {
                        $lineStart = true;
                    }
                }

                continue;
            }

            if ($rawDepth > 0 || $part === '') {
                continue;
            }

            $parts[$i] = $this->processText($part, $lineStart);

            if (trim(str_replace('&nbsp;', ' ', $part)) !== '') {
                $lineStart = false;
            }
        }

        return implode('', $parts);
    }

    /**
     * @param string $text кусок текста между тэгами
     * @param bool $lineStart кусок начинается с начала абзаца или строки
     */
    private function processText(string $text, bool $lineStart): string {
        if ($lineStart) {
            // «— Реплика»: тире не должно остаться одно в конце строки.
            $text = (string) preg_replace('/^(\s*)— +/u', '$1—' . self::NBSP, $text);
        } else {
            // «…,</i> — подумал он»: пробел перед тире стоит сразу после тэга.
            $text = (string) preg_replace('/^ +—/u', self::NBSP . '—', $text);
        }

        // «слово — слово»: тире не должно переноситься в начало строки.
        $text = (string) preg_replace('/(?<=\S) +—/u', self::NBSP . '—', $text);

        // Однобуквенные предлоги и союзы не остаются в конце строки.
        $text = (string) preg_replace(
            '/(?<![\p{L}\p{N}\-])([авиксоуяАВИКСОУЯ]) +(?=\S)/u',
            '$1' . self::NBSP,
            $text
        );

        // То же для английских вставок: артикли, «I», короткие предлоги и союзы
        // («Call of Duty», «two in the chest»).
        return (string) preg_replace(
            '/(?<![\p{L}\p{N}\-\'’])(' . self::EN_SHORT_WORDS . '|I) +(?=\S)/u',
            '$1' . self::NBSP,
            $text
        );
    }
}
