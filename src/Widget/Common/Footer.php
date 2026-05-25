<?php

declare(strict_types=1);

namespace BC\Widget\Common;

use BC\Core\Asset\IAssetBuilder;
use BC\Widget\Attribute\WidgetList;
use BC\Widget\AWidget;
use BC\Widget\Page\APage;
use Runway\Singleton\Container;

#[WidgetList('body', priority: 9999)]
class Footer extends AWidget {
    protected function getTemplatePath(): string {
        return 'common/footer.phtml';
    }

    private function getBundler(): IAssetBuilder {
        return Container::getInstance()->getService(IAssetBuilder::class);
    }

    protected function getStyleUrl(string $relativePath): string {
        return $this->getBundler()->getBundleWebPath($relativePath, 'css');
    }

    protected function getPage(): ?APage {
        $result = $this->context['page'] ?? null;

        return ($result instanceof APage)
            ? $result
            : null;
    }
}
