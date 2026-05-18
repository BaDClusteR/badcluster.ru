<?php

namespace BC\Widget\Common\Block;

use BC\Widget\AWidget;
use BC\Widget\DTO\TableOfContentsItemDTO;

class TableOfContentsMenu extends AWidget
{
    protected function getTemplatePath(): string {
        return 'common/block/table_of_contents/menu.phtml';
    }

    /**
     * @return TableOfContentsItemDTO[]
     */
    protected function getItems(): array {
        return (array)($this->context['items'] ?? []);
    }
}
