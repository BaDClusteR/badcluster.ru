<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Provider;

interface INotesProvider {
    /**
     * @return iterable<\BC\Modules\Blog\Model\Note>
     */
    public function getNotes(bool $onlyPublished): iterable;
}
