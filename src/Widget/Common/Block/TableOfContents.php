<?php

namespace BC\Widget\Common\Block;

use BC\Widget\AWidget;

class TableOfContents extends AWidget
{
    protected function getTemplatePath(): string {
        return 'common/block/table_of_contents.phtml';
    }

    protected function getChildren(?array $level): array {
        return (array)($level['children'] ?? []);
    }

    protected function getItems(): array {
        return (array)($this->context['items'] ?? []);
    }
}
