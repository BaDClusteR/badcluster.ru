<?php

declare(strict_types=1);

namespace BC\Widget\Common;

use BC\Core\Asset\IAssetBuilder;
use BC\Widget\Attribute\WidgetList;
use BC\Widget\AWidget;
use BC\Widget\Page\APage;
use Runway\Singleton\Container;

#[WidgetList('body', priority: 100000)]
class JsBundles extends AWidget {
    private ?APage $page = null;

    protected function applyContext(array $context): void {
        parent::applyContext($context);

        if (($this->context['page'] ?? null) instanceof APage) {
            $this->page = $this->context['page'];
        }
    }

    protected function getTemplatePath(): string {
        return 'common/js-bundles.phtml';
    }

    protected function getJsBundles(): array {
        return array_merge(
            ['scripts'],
            (array) $this->page?->getJsBundles()
        );
    }

    public function getBundler(): IAssetBuilder {
        return Container::getInstance()->getService(IAssetBuilder::class);
    }
}
