<?php

declare(strict_types=1);

namespace BC\Core\Action\DTO;

use BC\Model\PulseItem;

readonly class CreatePulseItemResponse {
    public function __construct(
        public PulseItem $item
    ) {
    }
}
