<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Controller;

use BC\Core\Auth\IAuth;
use BC\Core\Response\SuccessfulHtmlResponse;
use BC\Core\Trait\Controller404Trait;
use BC\Modules\Blog\Model\Note;
use BC\Modules\Blog\Provider\INotesProvider;
use BC\Modules\Blog\Widget\Page\NotePage;
use BC\Modules\Blog\Widget\Page\NotesPage;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Model\Exception\ModelException;
use Runway\Request\Response;
use Throwable;

readonly class Notes {
    use Controller404Trait;

    public function __construct(
        private IAuth $auth,
        private INotesProvider $notesProvider
    ) {
    }

    /**
     * @throws Throwable
     */
    public function renderNoteList(): Response {
        if (!$this->notesProvider->hasNotes()) {
            return $this->get404Controller()->run();
        }

        return new SuccessfulHtmlResponse(
            new NotesPage()->render()
        );
    }

    /**
     * @throws Throwable
     */
    public function renderNote(string $slug): Response {
        $note = $this->getNote($slug);

        if (!$note) {
            return $this->get404Controller()->run();
        }

        return new SuccessfulHtmlResponse(
            new NotePage(['note' => $note])->render()
        );
    }

    /**
     * @throws ModelException
     * @throws DBException
     * @throws QueryBuilderException
     */
    private function getNote(string $slug): ?Note {
        $conditions = ['slug' => $slug];

        if (!$this->auth->isAuthenticated()) {
            $conditions['published'] = true;
        }

        return Note::findOne($conditions);
    }
}
