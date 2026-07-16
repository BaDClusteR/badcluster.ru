<?php

namespace BC\Api\DTO\PulseItem;

readonly class PulseItemRowDTO {
    public function __construct(
        public int $id,
        public ?string $image,
        public string $title,
        public string $text,
        public int $position,
        /** Элемент добавлен автоматически (из IPulseItemsProvider), в админке не редактируется */
        public bool $isAuto = false
    ) {
    }
}
