<?php

namespace BC\Widget\Common\Header;

use BC\Widget\Attribute\WidgetList;
use BC\Widget\AWidget;

#[WidgetList('header')]
class MenuSwitcher extends AWidget
{
    protected function getTemplatePath(): string
    {
        return 'common/header/menu-switcher.phtml';
    }
}
