<?php

declare(strict_types=1);

namespace BC\Modules\Books\Core\DTO;

readonly class NoteDTO {
    public function __construct(
        public string $id,
        public string $content
    ) {
    }
}
