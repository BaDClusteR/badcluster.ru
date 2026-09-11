<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Core\Action\Note;

use BC\Modules\Blog\Core\Action\DTO\GetNoteRequest;
use BC\Modules\Blog\Core\Action\DTO\GetNoteResponse;
use BC\Modules\Blog\Model\Note;
use Runway\Exception\Exception;

readonly class GetNoteAction implements IGetNoteAction {
    /**
     * @throws Exception
     */
    public function run(GetNoteRequest $request): GetNoteResponse {
        $note = Note::findByUniqueIdentifier($request->id);

        if (!$note) {
            throw new Exception("Note #$request->id not found");
        }

        return new GetNoteResponse($note);
    }
}
