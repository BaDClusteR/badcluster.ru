<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Core\Action\DTO;

use BC\Modules\Blog\Model\Note;

readonly class CreateNoteResponse {
    public function __construct(
        public Note $note
    ) {
    }
}
