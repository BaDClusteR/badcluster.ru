<?php

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
