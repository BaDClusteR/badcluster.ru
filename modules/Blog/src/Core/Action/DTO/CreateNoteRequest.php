<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Core\Action\DTO;

use DateTime;

readonly class CreateNoteRequest {
    public function __construct(
        public string $title,
        public array $content,
        public string $slug,
        public string $metaDescription,
        public bool $published,
        public DateTime $publishDate
    ) {
    }
}
