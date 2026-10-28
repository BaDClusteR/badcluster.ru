<?php

declare(strict_types=1);

namespace BC\Widget\DTO;

use BC\Core\Helper\MixedValue;

readonly class TableOfContentsItemDTO {
    /**
     * @param self[] $children
     */
    public function __construct(
        public string $text,
        public string $anchor,
        public array $children = []
    ) {
    }

    /**
     * @param array<mixed> $items The items of the block: ['text' => ..., 'anchor' => ..., 'children' => [...]], the
     *                            ones that are not arrays are skipped.
     *
     * @return self[]
     */
    public static function buildItems(array $items): array {
        return array_map(
            static fn (array $item): TableOfContentsItemDTO => static::buildItem($item),
            array_filter($items, is_array(...))
        );
    }

    /**
     * @param array<mixed> $item ['text' => ..., 'anchor' => ..., 'children' => [...]]
     */
    public static function buildItem(array $item): self {
        $children = $item['children'] ?? [];

        return new self(
            text: MixedValue::toString($item['text'] ?? ''),
            anchor: MixedValue::toString($item['anchor'] ?? ''),
            children: static::buildItems(is_array($children) ? $children : [])
        );
    }
}
