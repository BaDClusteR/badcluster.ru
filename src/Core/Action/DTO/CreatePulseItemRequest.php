<?php

declare(strict_types=1);

namespace BC\Core\Action\DTO;

use BC\Model\Media;

readonly class CreatePulseItemRequest {
    public function __construct(
        public ?Media $image,
        public string $tag = '',
        public string $title = '',
        public string $text = '',
        public string $statusTitle = '',
        public string $statusText = '',
        public string $icon = '',
        public int $position = 0,
        public string $url = '',
        public bool $isTall = false,
        public bool $isSurfaced = false
    ) {
    }
}
