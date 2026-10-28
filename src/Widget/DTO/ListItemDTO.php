<?php

declare(strict_types=1);

namespace BC\Widget\DTO;

use BC\Core\Helper\MixedValue;

readonly class ListItemDTO {
    /**
     * @param ListItemDTO[] $children
     */
    public function __construct(
        public string $content,
        public array $children
    ) {
    }

    /**
     * @param array<mixed> $items The items of the list: ['content' => ..., 'items' => [...]], the ones that are not
     *                            arrays are skipped.
     *
     * @return ListItemDTO[]
     */
    public static function buildItems(array $items): array {
        return array_map(
            static fn (array $item) => self::buildItem($item),
            array_filter($items, is_array(...))
        );
    }

    /**
     * @param array<mixed> $item ['content' => ..., 'items' => [...]]
     */
    public static function buildItem(array $item): self {
        return new self(
            content: MixedValue::toString($item['content'] ?? ''),
            children: static::buildItems(
                (array) ($item['items'] ?? [])
            )
        );
    }
}
