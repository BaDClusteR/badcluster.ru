<?php

namespace BC\Widget\Head;

use BC\Widget\Attribute\WidgetList;
use BC\Widget\AWidget;
use BC\Widget\Page\APage;

#[WidgetList('head')]
class Title extends AWidget
{
    public function getTitle(): string {
        return (string)$this->getPage()?->getTitle();
    }

    protected function getTemplatePath(): string
    {
        return 'head/title.phtml';
    }

    public function getPage(): ?APage {
        return $this->context['page'] ?? null;
    }
}