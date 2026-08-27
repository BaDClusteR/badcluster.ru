<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Core\Action\DTO;

use DateTime;

readonly class SaveNoteRequest {
    public function __construct(
        public int $id,
        public string $title,
        public array $content,
        public string $slug,
        public string $metaDescription,
        public bool $published,
        public DateTime $publishDate
    ) {
    }
}
