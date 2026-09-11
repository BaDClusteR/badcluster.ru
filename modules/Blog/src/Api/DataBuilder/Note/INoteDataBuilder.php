<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Api\DataBuilder\Note;

use BC\Modules\Blog\Api\DTO\NoteDTO;
use BC\Modules\Blog\Api\DTO\NoteRowDTO;
use BC\Modules\Blog\Model\Note;

interface INoteDataBuilder {
    public function buildRow(Note $note): NoteRowDTO;

    public function buildEntity(Note $note): NoteDTO;
}
