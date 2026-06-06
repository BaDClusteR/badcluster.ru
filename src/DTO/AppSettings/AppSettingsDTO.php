<?php

declare(strict_types=1);

namespace BC\DTO\AppSettings;

use JsonException;
use RuntimeException;

class AppSettingsDTO {
    /**
     * @param NavigationDTO[] $nav
     * @param ModuleDTO[]     $modules
     */
    public function __construct(
        public array $nav,
        public array $modules,
        public string $webRoot,
        public string $staticRoot,
        public array $extra
    ) {
    }

    public function addNavItem(NavigationDTO $navItem): self {
        $this->nav[] = $navItem;

        return $this;
    }

    public function addExtra(string $key, mixed $value): self {
        $this->extra[$key] = $value;

        return $this;
    }

    public function addModule(ModuleDTO $module): self {
        $this->modules[] = $module;

        return $this;
    }

    public function toArray(): array {
        return [
            'nav'        => array_map(
                static fn (NavigationDTO $item) => $item->toArray(),
                $this->nav
            ),
            'modules'    => array_map(
                static fn (ModuleDTO $item) => $item->toArray(),
                $this->modules
            ),
            'webRoot'    => $this->webRoot,
            'staticRoot' => $this->staticRoot,
            ...$this->extra
        ];
    }

    public function toJsonString(): string {
        try {
            return json_encode($this->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $e) {
            throw new RuntimeException("Cannot encode app settings to JSON: {$e->getMessage()}");
        }
    }
}
