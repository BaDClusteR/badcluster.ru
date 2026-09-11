<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Core\Action\Note;

use BC\Modules\Blog\Core\Action\DTO\SaveNoteRequest;
use Runway\Exception\Exception;

interface ISaveNoteAction {
    /**
     * @throws Exception
     */
    public function run(SaveNoteRequest $request): void;
}
