<?php

declare(strict_types=1);

namespace BC\Widget\Common\Block;

use BC\Widget\AWidget;

class WList extends AWidget {
    protected function getTemplatePath(): string {
        return 'common/block/list.phtml';
    }

    protected function getStyle(): string {
        return (string) ($this->context['style'] ?? '');
    }

    protected function getTag(): string {
        return $this->getStyle() === 'ordered'
            ? 'ol'
            : 'ul';
    }

    /**
     * @return string[]
     */
    protected function getItems(): array {
        return (array) ($this->context['items'] ?? []);
    }
}
