<?php

declare(strict_types=1);

namespace BC\Api\DTO\StaticPage;

readonly class StaticPageRowDTO {
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public bool $published,
        public string $created_date
    ) {
    }
}
