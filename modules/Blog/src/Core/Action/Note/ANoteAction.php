<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Core\Action\Note;

use BC\Core\Trait\BlockHelperTrait;
use BC\Modules\Blog\Core\Action\DTO\CreateNoteRequest;
use BC\Modules\Blog\Core\Action\DTO\SaveNoteRequest;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;
use BC\Modules\Blog\Core\Action\Validator\INoteValidator;
use BC\Modules\Blog\Model\Note;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Model\Exception\ModelException;
use Runway\Singleton\Container;

abstract class ANoteAction {
    use BlockHelperTrait;

    /**
     * @throws DBException
     * @throws QueryBuilderException
     * @throws ModelException
     */
    protected function syncModel(Note $note, CreateNoteRequest|SaveNoteRequest $request): void {
        $note->setTitle($request->title)
             ->setContent(
                 $this->getBlockHelper()->cleanBlocks(
                     $request->content
                 )
             )
             ->setSlug($request->slug)
             ->setPublished($request->published)
             ->setPublishDate($request->publishDate)
             ->setMetaDescription($request->metaDescription);

        $note->persist();
    }

    protected function getValidator(): INoteValidator {
        return Container::getInstance()->getService(INoteValidator::class);
    }

    /**
     * @throws ActionValidationException
     */
    protected function validate(CreateNoteRequest|SaveNoteRequest $request): void {
        $validationResponse = $this->getValidator()->validate($request);

        if (!$validationResponse->successful) {
            throw new ActionValidationException($validationResponse->errors);
        }
    }
}
