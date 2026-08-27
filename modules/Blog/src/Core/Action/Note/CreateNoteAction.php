<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Core\Action\Note;

use BC\Modules\Blog\Core\Action\DTO\CreateNoteRequest;
use BC\Modules\Blog\Core\Action\DTO\CreateNoteResponse;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;
use BC\Modules\Blog\Model\Note;
use DateTime;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Model\Exception\ModelException;

class CreateNoteAction extends ANoteAction implements ICreateNoteAction {
    /**
     * @throws ActionValidationException
     * @throws DBException
     * @throws ModelException
     * @throws QueryBuilderException
     */
    public function run(CreateNoteRequest $request): CreateNoteResponse {
        $this->validate($request);

        $note = new Note();
        $note->setCreatedDate(
            new DateTime('now')
        );

        $this->syncModel($note, $request);

        return new CreateNoteResponse(
            note: $note
        );
    }
}
