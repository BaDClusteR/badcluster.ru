<?php

namespace BC\Provider;

use BC\Widget\Page\APage;

interface IPreloadFontProvider {
    /**
     * @return string[]
     */
    public function getPreloadFontPaths(APage $page): array;
}
