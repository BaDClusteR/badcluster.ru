<?php

declare(strict_types=1);

namespace BC\Api\DTO\Attention;

readonly class AttentionItemDTO {
    public function __construct(
        public string $message,
        public string $severity,
        public string $adminPath
    ) {
    }
}
