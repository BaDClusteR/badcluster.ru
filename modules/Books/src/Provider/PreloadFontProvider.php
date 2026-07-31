<?php

namespace BC\Modules\Books\Provider;

use BC\Modules\Books\Widget\Page\ChapterPage;
use BC\Provider\IPreloadFontProvider;
use BC\Widget\Page\APage;

readonly class PreloadFontProvider implements IPreloadFontProvider {
    public function __construct(
        private IPreloadFontProvider $inner
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getPreloadFontPaths(APage $page): array {
        $fonts = $this->inner->getPreloadFontPaths($page);

        if ($page instanceof ChapterPage) {
            $fonts[] = 'ibm-plex-serif/cyrillic.woff2';
            $fonts[] = 'ibm-plex-serif/latin.woff2';
        }

        return $fonts;
    }
}
