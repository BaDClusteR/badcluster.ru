<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Core\Action\Note;

use BC\Modules\Blog\Core\Action\DTO\GetNoteRequest;
use BC\Modules\Blog\Core\Action\DTO\GetNoteResponse;

interface IGetNoteAction {
    public function run(GetNoteRequest $request): GetNoteResponse;
}
