<?php

declare(strict_types=1);

namespace BC\Core\Shortcode;

/**
 * Expands the small set of dynamic placeholders allowed inside static pages.
 *
 * Deliberately not a general shortcode engine: no parameters, no nesting, no
 * user-defined codes — just a fixed list of names that the site knows how to
 * compute. See the class constants for the current set.
 */
interface IShortcodeResolver {
    /**
     * Replaces inline shortcodes (the ones that stand for a piece of text)
     * inside a fragment. Unknown codes are left untouched.
     */
    public function expand(string $text): string;

    /**
     * Renders a block-level shortcode — one that stands for a whole widget and
     * therefore cannot sit inside a paragraph. Returns null if the name is not
     * a block-level shortcode.
     */
    public function renderBlock(string $name): ?string;

    /**
     * Name of the block-level shortcode a text fragment consists of, if the
     * fragment is nothing but that shortcode. Null otherwise.
     */
    public function detectBlockShortcode(string $text): ?string;
}
