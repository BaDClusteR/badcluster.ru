<?php

declare(strict_types=1);

namespace BC\DTO\AppSettings;

readonly class NavigationDTO {
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

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array {
        return [
            'label'    => $this->label,
            'path'     => $this->path,
            'icon'     => $this->icon,
            'position' => $this->position,
            'children' => ($this->children !== null)
                ? array_map(
                    static fn (NavigationDTO $item) => $item->toArray(),
                    $this->children
                )
                : null
        ];
    }
}
