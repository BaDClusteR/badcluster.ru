<?php

namespace BC\Widget\Common;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Core\Trait\WebsiteSettingsTrait;
use BC\Widget\Attribute\WidgetList;
use BC\Widget\AWidget;
use BC\Widget\IAssetProvider;
use BC\Widget\Page\APage;

#[WidgetList('body')]
class Content extends AWidget
{
    protected function getTemplatePath(): string
    {
        return 'common/content.phtml';
    }

    protected function getPage(): ?APage {
        $result = $this->context['page'] ?? null;

        return ($result instanceof APage)
            ? $result
            : null;
    }
}
