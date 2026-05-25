<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Api\DTO;

readonly class TagDTO {
    public function __construct(
        public string $title,
        public string $slug,
        public string $description
    ) {
    }
}
