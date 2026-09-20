<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Core\Action\Note;

use BC\Modules\Blog\Core\Action\DTO\CreateNoteRequest;
use BC\Modules\Blog\Core\Action\DTO\CreateNoteResponse;
use Runway\Exception\Exception;

interface ICreateNoteAction {
    /**
     * @throws Exception
     */
    public function run(CreateNoteRequest $request): CreateNoteResponse;
}
