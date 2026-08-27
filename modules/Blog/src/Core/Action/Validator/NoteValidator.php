<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Core\Action\Validator;

use BC\Modules\Blog\Core\Action\DTO\CreateNoteRequest;
use BC\Modules\Blog\Core\Action\DTO\SaveNoteRequest;
use BC\Modules\Blog\Core\Action\DTO\ValidatorResponse;
use BC\Modules\Blog\Model\Note;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Model\Exception\ModelException;

class NoteValidator implements INoteValidator {
    /**
     * @throws ModelException
     * @throws DBException
     * @throws QueryBuilderException
     */
    public function validate(SaveNoteRequest|CreateNoteRequest $request): ValidatorResponse {
        if ($noteWithTheSameSlug = $this->getNoteWithTheSameSlug($request)) {
            return new ValidatorResponse(
                successful: false,
                errors: [
                    'slug' => sprintf(
                        'Такой слаг уже занят заметкой #%d "%s"',
                        $noteWithTheSameSlug->getId(),
                        $noteWithTheSameSlug->getTitle()
                    ),
                ]
            );
        }

        return new ValidatorResponse();
    }

    /**
     * @throws DBException
     * @throws ModelException
     * @throws QueryBuilderException
     */
    private function getNoteWithTheSameSlug(SaveNoteRequest|CreateNoteRequest $request): ?Note {
        $qb = Note::getQueryBuilder()
                  ->andWhere('LOWER(slug) = :slug')
                  ->setVariable('slug', strtolower($request->slug));

        if ($request instanceof SaveNoteRequest) {
            $qb = $qb->andWhere('id != :noteId')
                     ->setVariable('noteId', $request->id);
        }

        /** @var Note|null $result */
        $result = $qb->getFirstEntity();

        return $result;
    }
}
