<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Api\DTO;

readonly class NoteRowDTO {
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public bool $published,
        public string $publish_date
    ) {
    }
}
