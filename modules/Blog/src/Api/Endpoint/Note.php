<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Api\Endpoint;

use ApiPlatform\Attribute as API;
use ApiPlatform\Attribute\Docs;
use ApiPlatform\Exception\BadRequestException;
use BC\Api\DTO\CreatedDTO;
use BC\Api\DTO\GetEntitiesListRequest;
use BC\Api\DTO\ListResponseDTO;
use BC\Api\DTO\SuccessfulResultDTO;
use BC\Api\Endpoint\AEndpoint;
use BC\Api\Exception\NotFoundException;
use BC\Core\Converter\IDateConverter;
use BC\Exception\UnprocessableEntityException;
use BC\Modules\Blog\Api\DataBuilder\Note\INoteDataBuilder;
use BC\Modules\Blog\Api\DTO\NoteDTO;
use BC\Modules\Blog\Api\DTO\NoteRowDTO;
use BC\Modules\Blog\Core\Action\DTO\CreateNoteRequest;
use BC\Modules\Blog\Core\Action\DTO\GetNoteRequest;
use BC\Modules\Blog\Core\Action\DTO\SaveNoteRequest;
use BC\Modules\Blog\Core\Action\Note\ICreateNoteAction;
use BC\Modules\Blog\Core\Action\Note\IGetNoteAction;
use BC\Modules\Blog\Core\Action\Note\ISaveNoteAction;
use BC\Modules\Blog\Model\Note as NoteModel;
use Runway\Singleton\Container;

#[Docs\Group('Notes')]
class Note extends AEndpoint {
    public function __construct(
        private readonly IDateConverter $dateConverter,
        private readonly INoteDataBuilder $dataBuilder
    ) {
    }

    /**
     * @return ListResponseDTO<NoteRowDTO>
     *
     * @throws BadRequestException
     */
    #[API\Endpoint(path: 'notes', method: 'GET')]
    public function getList(
        #[API\Parameter(source: 'query')]
        string $filter = '',
        #[API\Parameter(source: 'query')]
        string $sortBy = '',
        #[API\Parameter(source: 'query')]
        string $sortDir = '',
        #[API\Parameter(source: 'query')]
        int $page = 1,
        #[API\Parameter(source: 'query')]
        int $perPage = self::PER_PAGE_DEFAULT
    ): ListResponseDTO {
        return $this->getEntitiesList(
            new GetEntitiesListRequest(
                qb: NoteModel::getQueryBuilder()->orderBy('publish_date', 'DESC'),
                filter: $filter,
                columnsToFind: ['title'],
                sortBy: $sortBy,
                sortDir: $sortDir,
                page: $page,
                perPage: $perPage,
                sortableColumns: ['title', 'slug', 'published', 'publish_date']
            ),
            fn (NoteModel $note): NoteRowDTO => $this->dataBuilder->buildRow($note)
        );
    }

    /**
     * @throws NotFoundException
     */
    #[API\Endpoint(path: 'note', method: 'GET')]
    public function getOne(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id
    ): NoteDTO {
        $action = Container::getInstance()->getService(IGetNoteAction::class);

        $note = $this->handleWithException(
            static fn () => $action->run(
                new GetNoteRequest($id)
            )->note
        );

        return $this->handleWithException(
            fn (): NoteDTO => $this->dataBuilder->buildEntity($note)
        );
    }

    /**
     * @throws UnprocessableEntityException
     */
    #[API\Endpoint(path: 'note', method: 'POST')]
    public function createNote(
        #[API\Parameter(source: 'body', name: 'title')]
        string $title,
        #[API\Parameter(source: 'body', name: 'content')]
        array $content,
        #[API\Parameter(source: 'body', name: 'publishDate')]
        string $publishDate,
        #[API\Parameter(source: 'body', name: 'slug')]
        string $slug,
        #[API\Parameter(source: 'body', name: 'published')]
        bool $published = false,
        #[API\Parameter(source: 'body', name: 'metaDescription')]
        string $metaDescription = ''
    ): CreatedDTO {
        $response = null;
        $request = $this->handleWithException(
            fn () => new CreateNoteRequest(
                title: $title,
                content: $content,
                slug: $slug,
                metaDescription: $metaDescription,
                published: $published,
                publishDate: $this->dateConverter->toDateTime($publishDate)
            )
        );

        $this->handleActionWithException(
            function () use (&$response, $request) {
                $action = Container::getInstance()->getService(ICreateNoteAction::class);
                $response = $action->run($request);
            },
            'Ошибки при создании заметки'
        );

        return new CreatedDTO(
            $response->note->getId()
        );
    }

    /**
     * @throws UnprocessableEntityException
     */
    #[API\Endpoint(path: 'note', method: 'PUT')]
    public function saveNote(
        #[API\Parameter(source: 'body', name: 'title')]
        string $title,
        #[API\Parameter(source: 'body', name: 'content')]
        array $content,
        #[API\Parameter(source: 'body', name: 'publishDate')]
        string $publishDate,
        #[API\Parameter(source: 'body', name: 'slug')]
        string $slug,
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id,
        #[API\Parameter(source: 'body', name: 'published')]
        bool $published = false,
        #[API\Parameter(source: 'body', name: 'metaDescription')]
        string $metaDescription = ''
    ): SuccessfulResultDTO {
        $request = $this->handleWithException(
            fn () => new SaveNoteRequest(
                id: $id,
                title: $title,
                content: $content,
                slug: $slug,
                metaDescription: $metaDescription,
                published: $published,
                publishDate: $this->dateConverter->toDateTime($publishDate)
            )
        );

        $this->handleActionWithException(
            function () use ($request) {
                $action = Container::getInstance()->getService(ISaveNoteAction::class);
                $action->run($request);
            },
            'Ошибки при сохранении заметки'
        );

        return new SuccessfulResultDTO();
    }

    #[API\Endpoint(path: 'notes', method: 'DELETE')]
    public function deleteNotes(
        #[API\Parameter(source: 'body', name: 'rows')]
        array $rows
    ): SuccessfulResultDTO {
        $this->deleteEntities(NoteModel::class, $rows);

        return new SuccessfulResultDTO();
    }
}
