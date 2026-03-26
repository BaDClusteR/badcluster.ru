<?php

namespace BC\Widget\Head;

use BC\Core\Trait\WebsiteSettingsTrait;
use BC\Widget\Attribute\WidgetList;
use BC\Widget\AWidget;

#[WidgetList('head')]
class Favicon extends AWidget
{
    use WebsiteSettingsTrait;

    protected function getTemplatePath(): string
    {
        return 'head/favicon.phtml';
    }

    public function getIconsWebPath(): string {
        return $this->getWebsiteSettings()->getWebRoot();
    }
}
