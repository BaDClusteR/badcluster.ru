<?php

declare(strict_types=1);

namespace BC\Widget\Common\Block;

use BC\Core\Helper\MixedValue;
use BC\Widget\AWidget;
use BC\Widget\DTO\TableOfContentsItemDTO;

class TableOfContents extends AWidget {
    protected function getTemplatePath(): string {
        return 'common/block/table_of_contents/widget.phtml';
    }

    /**
     * @return TableOfContentsItemDTO[]
     */
    protected function getItems(): array {
        return TableOfContentsItemDTO::buildItems(
            (array) ($this->context['items'] ?? [])
        );
    }

    protected function getLabel(): string {
        return MixedValue::toString($this->context['label'] ?? '');
    }
}
