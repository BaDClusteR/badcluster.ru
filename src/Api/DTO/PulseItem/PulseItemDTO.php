<?php

namespace BC\Api\DTO\PulseItem;

use BC\Core\DTO\MediaDTO;

readonly class PulseItemDTO {
    public function __construct(
        public ?MediaDTO $image,
        public string $tag,
        public string $title,
        public string $text,
        public string $statusTitle,
        public string $statusText,
        public string $icon,
        public int $position,
        public string $url,
        public bool $isTall,
        public bool $isSurfaced
    ) {
    }
}
