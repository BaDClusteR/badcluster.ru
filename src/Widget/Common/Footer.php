<?php

declare(strict_types=1);

namespace BC\Widget\Common;

use BC\Core\Asset\IAssetBuilder;
use BC\Core\Trait\AssetBuilderTrait;
use BC\Widget\Attribute\WidgetList;
use BC\Widget\AWidget;
use BC\Widget\Page\APage;
use Runway\Singleton\Container;

#[WidgetList('body', priority: 9999)]
class Footer extends AWidget {
    protected function getTemplatePath(): string {
        return 'common/footer.phtml';
    }

    protected function getPage(): ?APage {
        $result = $this->context['page'] ?? null;

        return ($result instanceof APage)
            ? $result
            : null;
    }
}
