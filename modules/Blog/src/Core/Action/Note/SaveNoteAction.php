<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Core\Action\Note;

use BC\Modules\Blog\Core\Action\DTO\SaveNoteRequest;
use BC\Modules\Blog\Model\Note;
use Runway\Exception\Exception;

class SaveNoteAction extends ANoteAction implements ISaveNoteAction {
    /**
     * @throws Exception
     */
    public function run(SaveNoteRequest $request): void {
        $this->validate($request);

        $note = Note::findByUniqueIdentifier($request->id);

        if (!$note) {
            throw new Exception("Note #$request->id not found");
        }

        $this->syncModel($note, $request);
    }
}
