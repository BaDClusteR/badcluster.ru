<?php

namespace BC\DTO\AppSettings;

readonly class NavigationDTO
{
    /**
     * @param NavigationDTO[]|null $children
     */
    public function __construct(
        public string $label,
        public ?string $path = null,
        public ?string $icon = null,
        public int $position = 0,
        public ?array $children = []
    ) {
    }

    public function toArray(): array {
        return [
            'label'    => $this->label,
            'path'     => $this->path,
            'icon'     => $this->icon,
            'position' => $this->position,
            'children' => array_map(
                static fn(NavigationDTO $item) => $item->toArray(),
                $this->children
            )
        ];
    }
}
