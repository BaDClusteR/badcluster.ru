<?php

declare(strict_types=1);

namespace BC\Modules\Books\Core\DTO;

readonly class ExtractedNotesWithContentDTO {
    /**
     * @param NoteDTO[] $notes
     */
    public function __construct(
        public string $content,
        public array $notes
    ) {
    }
}
