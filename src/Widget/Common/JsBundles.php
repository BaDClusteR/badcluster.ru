<?php

declare(strict_types=1);

namespace BC\Widget\Common;

use BC\Core\Asset\IAssetBuilder;
use BC\Core\Trait\AssetBuilderTrait;
use BC\Widget\Attribute\WidgetList;
use BC\Widget\AWidget;
use BC\Widget\Page\APage;
use BC\Widget\Page\Page404;
use Runway\Singleton\Container;

#[WidgetList('body', priority: 100000)]
class JsBundles extends AWidget {
    use AssetBuilderTrait;

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
        $coreScripts = [];

        if (!($this->page instanceof Page404)) {
            $coreScripts[] = 'scripts';
        }

        return array_merge(
            $coreScripts,
            (array) $this->page?->getJsBundles()
        );
    }
}
