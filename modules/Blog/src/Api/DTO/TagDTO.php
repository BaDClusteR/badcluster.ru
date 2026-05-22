<?php

namespace BC\Modules\Blog\Api\DTO;

readonly class TagDTO {
    public function __construct(
        public string $title,
        public string $slug
    ) {
    }
}
