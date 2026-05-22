<?php

namespace BC\Api\DTO;

/**
 * @template T
 */
readonly class ListResponseDTO {
    /**
     * @param T[] $items
     */
    public function __construct(
        public array $items,
        public int $total
    ) {
    }
}
