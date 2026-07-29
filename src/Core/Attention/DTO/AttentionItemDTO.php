<?php

declare(strict_types=1);

namespace BC\Core\Attention\DTO;

use BC\Core\Attention\Enum\AttentionSeverityEnum;

readonly class AttentionItemDTO {
    public function __construct(
        public string $message,
        public AttentionSeverityEnum $severity,
        /** Относительный путь в админке; пустой — элемент некликабелен */
        public string $adminPath = ''
    ) {
    }
}
