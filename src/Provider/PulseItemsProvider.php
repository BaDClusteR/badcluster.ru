<?php

declare(strict_types=1);

namespace BC\Provider;

use BC\Core\Trait\WebsiteSettingsTrait;
use BC\DTO\PulseItemDTO;

class PulseItemsProvider implements IPulseItemsProvider {
    use WebsiteSettingsTrait;

    public function getPulseItems(): array {
        $items = $this->getPulseItemsUnsorted();

        usort(
            $items,
            static fn (PulseItemDTO $a, PulseItemDTO $b): int => $a->position <=> $b->position
        );

        return $items;
    }

    protected function getPulseItemsUnsorted(): array {
        return [];
    }
}
