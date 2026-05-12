<?php

namespace BC\Widget\Head;

use BC\Core\Asset\IAssetBuilder;
use BC\Widget\Attribute\WidgetList;
use BC\Widget\AWidget;
use BC\Widget\Page\APage;
use Runway\Singleton\Container;

#[WidgetList('head')]
class Bundles extends AWidget
{
    protected ?APage $page = null;

    protected function applyContext(array $context): void
    {
        parent::applyContext($context);

        if (($context['page'] ?? null) instanceof APage) {
            $this->page = $context['page'];
        }
    }

    protected function getTemplatePath(): string
    {
        return 'head/bundles.phtml';
    }

    public function getJsBundles(): array {
        return (array)$this->page?->getJsBundles();
    }

    public function getCssBundles(): array {
        return (array)$this->page?->getCssBundles();
    }

    public function getBundler(): IAssetBuilder {
        return Container::getInstance()->getService(IAssetBuilder::class);
    }
}
