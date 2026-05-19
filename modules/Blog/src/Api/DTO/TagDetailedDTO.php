<?php

namespace BC\Modules\Blog\Api\DTO;

readonly class TagDetailedDTO {
    public function __construct(
        public string $title,
        public string $slug
    ) {
    }
}
