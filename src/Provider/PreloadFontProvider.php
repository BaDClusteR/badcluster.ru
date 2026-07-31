<?php

namespace BC\Provider;

use BC\Widget\Page\APage;

class PreloadFontProvider implements IPreloadFontProvider {

    /**
     * @inheritDoc
     */
    public function getPreloadFontPaths(APage $page): array {
        return [
            'ibm-plex-sans/cyrillic.woff2',
            'ibm-plex-sans/latin.woff2'
        ];
    }
}
