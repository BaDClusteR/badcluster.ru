<?php

declare(strict_types=1);

namespace BC\Widget\DTO;

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
     * @param array{text: string, anchor: string, children: array}[] $items
     *
     * @return self[]
     */
    public static function buildItems(array $items): array {
        return array_map(
            static fn (array $item): TableOfContentsItemDTO => static::buildItem($item),
            $items
        );
    }

    public static function buildItem(array $item): self {
        return new self(
            text: (string) ($item['text'] ?? ''),
            anchor: (string) ($item['anchor'] ?? ''),
            children: static::buildItems($item['children'] ?? [])
        );
    }
}
