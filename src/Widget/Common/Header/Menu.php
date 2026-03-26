<?php

namespace BC\Widget\Common\Header;

use BC\Widget\Attribute\WidgetList;
use BC\Widget\AWidget;

#[WidgetList('header', priority: 1000)]
class Menu extends AWidget
{
    protected function getTemplatePath(): string
    {
        return 'common/header/menu.phtml';
    }
}
