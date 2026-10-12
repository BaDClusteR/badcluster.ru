<?php

declare(strict_types=1);

namespace BC\Widget\Common\Block;

use BC\Core\Trait\TypographerTrait;
use BC\Widget\AWidget;

class WList extends AWidget {
    use TypographerTrait;

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
        return array_map(
            fn (mixed $item): mixed => is_string($item)
                ? $this->getTypographer()->process($item)
                : $item,
            (array) ($this->context['items'] ?? [])
        );
    }
}
