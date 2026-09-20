<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Provider;

use BC\Modules\Blog\Model\Note;

interface INotesProvider {
    /**
     * @return iterable<Note>
     */
    public function getNotes(bool $onlyPublished): iterable;

    public function hasNotes(): bool;
}
