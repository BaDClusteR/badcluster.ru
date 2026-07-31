<?php

namespace BC\Widget\Head;

use BC\Core\Trait\PathsProviderTrait;
use BC\Provider\IPreloadFontProvider;
use BC\Provider\PreloadFontProvider;
use BC\Widget\Attribute\WidgetList;
use BC\Widget\AWidget;
use BC\Widget\Page\APage;
use Runway\Singleton\Container;

#[WidgetList('head', priority: -999)]
class Preload extends AWidget {
    use PathsProviderTrait;

    protected function getTemplatePath(): string {
        return 'head/preload.phtml';
    }

    /**
     * @return string[]
     */
    protected function getFonts(): array {
        return $this->getPreloadFontProvider()->getPreloadFontPaths(
            $this->getPage()
        );
    }

    private function getPreloadFontProvider(): IPreloadFontProvider {
        return Container::getInstance()->getService(IPreloadFontProvider::class);
    }

    private function getPage(): APage {
        return $this->context['page'];
    }
}
