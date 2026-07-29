<?php

declare(strict_types=1);

namespace BC\Api\DTO\Attention;

readonly class AttentionDTO {
    /**
     * @param AttentionItemDTO[] $items
     */
    public function __construct(
        public array $items
    ) {
    }
}
