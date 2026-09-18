<?php

declare(strict_types=1);

namespace BC\Widget\Page\StaticPage;

use BC\Core\Shortcode\IShortcodeResolver;
use BC\Widget\Blocks;
use Runway\Singleton\Container;

/**
 * Block renderer for static pages: the same blocks as everywhere else, plus
 * shortcode expansion.
 *
 * Static pages replace templates that used to compute things in PHP (the site's
 * age on /history, mine on /me), so their text needs a way to stay dynamic.
 *
 * Two shapes, because they are genuinely different:
 *  - inline codes ([site_age], [my_age]) are substituted inside the text of
 *    paragraphs and headings;
 *  - a block code ([news]) stands for a whole widget, so a paragraph consisting
 *    of nothing but that code is replaced by the widget. Inlining it would put
 *    an <h2> and a grid inside a <p>.
 */
class StaticPageBlocks extends Blocks {
    protected function renderBlock(string $type, array $data): string {
        if (!in_array($type, ['paragraph', 'header'], true)) {
            return parent::renderBlock($type, $data);
        }

        $text = (string) ($data['text'] ?? '');
        $resolver = $this->getShortcodeResolver();

        if ($type === 'paragraph' && $name = $resolver->detectBlockShortcode($text)) {
            return (string) $resolver->renderBlock($name);
        }

        $data['text'] = $resolver->expand($text);

        return parent::renderBlock($type, $data);
    }

    private function getShortcodeResolver(): IShortcodeResolver {
        return Container::getInstance()->getService(IShortcodeResolver::class);
    }
}
