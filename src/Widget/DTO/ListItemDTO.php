<?php

namespace BC\Widget\DTO;

readonly class ListItemDTO
{
    /**
     * @param ListItemDTO[] $children
     */
    public function __construct(
        public string $content,
        public array $children
    ) {
    }

    /**
     * @return ListItemDTO[]
     */
    public static function buildItems(array $items): array {
        return array_map(
            static fn(array $item) => self::buildItem($item),
            $items
        );
    }

    public static function buildItem(array $item): self {
        return new self(
            content: (string)($item['content'] ?? ""),
            children: static::buildItems(
                (array)($item['items'] ?? [])
            )
        );
    }
}
