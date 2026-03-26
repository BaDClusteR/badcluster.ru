<?php

namespace BC\Widget\Common\Header;

use BC\Core\Trait\WebsiteSettingsTrait;
use BC\Widget\Attribute\WidgetList;
use BC\Widget\AWidget;

#[WidgetList('header', priority: 0)]
class Logo extends AWidget
{
    use WebsiteSettingsTrait;

    protected function getTemplatePath(): string
    {
        return 'common/header/logo.phtml';
    }

    protected function getLogoUrl(): string {
        return $this->getWebsiteSettings()->getWebRoot();
    }
}
