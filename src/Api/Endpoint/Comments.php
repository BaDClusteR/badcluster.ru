<?php

declare(strict_types=1);

namespace BC\Api\Endpoint;

use ApiPlatform\Attribute as API;
use ApiPlatform\Exception\BadRequestException;
use BC\Api\DataBuilder\Comment\ICommentDataBuilder;
use BC\Api\DTO\Comment\CommentDTO;
use BC\Api\DTO\Comment\CommentRowDTO;
use BC\Api\DTO\GetEntitiesListRequest;
use BC\Api\DTO\ListResponseDTO;
use BC\Api\DTO\SuccessfulResultDTO;
use BC\Api\Exception\NotFoundException;
use BC\Core\Converter\IDateConverter;
use BC\Exception\UnprocessableEntityException;
use BC\Model\Comment;

class Comments extends AEndpoint {
    public function __construct(
        private readonly IDateConverter $dateConverter,
        private readonly ICommentDataBuilder $dataBuilder
    ) {
    }

    /**
     * @return ListResponseDTO<CommentRowDTO>
     *
     * @throws BadRequestException
     */
    #[API\Endpoint(path: 'comments', method: 'GET')]
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
                qb: Comment::getQueryBuilder()->orderBy('date', 'DESC'),
                filter: $filter,
                columnsToFind: ['name', 'email', 'comment'],
                sortBy: $sortBy,
                sortDir: $sortDir,
                page: $page,
                perPage: $perPage,
                sortableColumns: ['date', 'name']
            ),
            fn (Comment $comment): CommentRowDTO => $this->dataBuilder->buildRow($comment)
        );
    }

    /**
     * @throws NotFoundException
     */
    #[API\Endpoint(path: 'comment', method: 'GET')]
    public function getOne(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id
    ): CommentDTO {
        return $this->getEntity(
            Comment::class,
            $id,
            'Коммент #{{id}} не найден.',
            fn (Comment $comment): CommentDTO => $this->dataBuilder->buildEntity($comment)
        );
    }

    /**
     * @throws UnprocessableEntityException
     * @throws NotFoundException
     */
    #[API\Endpoint(path: 'comment', method: 'PUT')]
    public function saveComment(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id,
        #[API\Parameter(source: 'body', name: 'date')]
        string $date,
        #[API\Parameter(source: 'body', name: 'name')]
        string $name,
        #[API\Parameter(source: 'body', name: 'comment')]
        string $text,
        #[API\Parameter(source: 'body', name: 'status')]
        string $status
    ): SuccessfulResultDTO {
        if (!in_array($status, Comment::getAllowedStatuses(), true)) {
            throw new UnprocessableEntityException(['status' => "Некорректный статус: $status"]);
        }

        $comment = $this->handleWithException(
            static fn () => Comment::findByUniqueIdentifier($id)
        );

        if (!$comment) {
            throw new NotFoundException("Коммент #$id не найден.");
        }

        $this->handleWithException(
            fn () => $comment->setDate($this->dateConverter->toDateTime($date))
                            ->setName($name)
                            ->setComment($text)
                            ->setStatus($status)
                            ->persist()
        );

        return new SuccessfulResultDTO();
    }

    #[API\Endpoint(path: 'comments', method: 'DELETE')]
    public function deleteComments(
        #[API\Parameter(source: 'body', name: 'rows')]
        array $rows
    ): SuccessfulResultDTO {
        $this->deleteEntities(Comment::class, $rows);

        return new SuccessfulResultDTO();
    }
}
