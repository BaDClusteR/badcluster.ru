<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Api\DTO;

readonly class TagRowDTO {
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public int $count
    ) {
    }
}
