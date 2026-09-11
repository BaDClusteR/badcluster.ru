<?php

declare(strict_types=1);

namespace BC\Core\Shortcode;

use BC\Core\Formatter\IFormatter;
use BC\Widget\Page\Home\Pulse;
use DateTime;

class ShortcodeResolver implements IShortcodeResolver {
    /**
     * The site's own birthday and mine. Same dates the hardcoded
     * BC\Widget\Page\About\AboutMe widget has always used.
     */
    private const string SITE_BIRTH_DATE = '2005-08-02';
    private const string MY_BIRTH_DATE = '1991-02-01 11:30:00';

    /** Rounding step for [site_age_round], matching the old /history heading. */
    private const int AGE_ROUNDING = 5;

    /** Shortcodes that stand for a piece of text and may sit mid-sentence. */
    private const array INLINE = ['site_age_round', 'site_age', 'my_age'];

    /** Shortcodes that stand for a whole widget and must own their block. */
    private const array BLOCK = ['news'];

    public function __construct(
        private readonly IFormatter $formatter
    ) {
    }

    public function expand(string $text): string {
        if (!str_contains($text, '[')) {
            return $text;
        }

        foreach (self::INLINE as $name) {
            if (str_contains($text, "[$name]")) {
                $text = str_replace("[$name]", $this->resolveInline($name), $text);
            }
        }

        return $text;
    }

    public function renderBlock(string $name): ?string {
        return match ($name) {
            'news' => new Pulse()->render(),
            default => null
        };
    }

    public function detectBlockShortcode(string $text): ?string {
        // Editor.js stores paragraph text as HTML, so strip the markup before
        // matching: the author typed "[news]", not necessarily bare "[news]".
        $plain = trim(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5));

        if (!preg_match('/^\[([a-z_]+)]$/', $plain, $matches)) {
            return null;
        }

        return in_array($matches[1], self::BLOCK, true)
            ? $matches[1]
            : null;
    }

    private function resolveInline(string $name): string {
        return match ($name) {
            'site_age' => $this->getAge(new DateTime(self::SITE_BIRTH_DATE)),
            // "21 год" -> "20 лет": the /history heading has always spoken in
            // round numbers, while body text wants the real figure.
            'site_age_round' => $this->getAge(new DateTime(self::SITE_BIRTH_DATE), self::AGE_ROUNDING),
            'my_age' => $this->getAge(new DateTime(self::MY_BIRTH_DATE)),
            default => "[$name]"
        };
    }

    private function getAge(DateTime $fromDate, int $roundDownTo = 1): string {
        $years = new DateTime()->diff($fromDate)->y;

        if ($roundDownTo > 1) {
            $years -= $years % $roundDownTo;
        }

        // Non-breaking space so "20 лет" never splits across lines — the old
        // /history heading did the same, and it reads better in body text too.
        return str_replace(
            ' ',
            '&nbsp;',
            $this->formatter->formatAsWordForm(
                $years,
                'год',
                'года',
                'лет'
            )
        );
    }
}
