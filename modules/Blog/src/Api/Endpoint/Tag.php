<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Api\Endpoint;

use ApiPlatform\Attribute as API;
use ApiPlatform\Exception\BadRequestException;
use ApiPlatform\Exception\RuntimeInternalErrorException;
use BC\Api\DTO\CreatedDTO;
use BC\Api\DTO\ListResponseDTO;
use BC\Api\DTO\SuccessfulResultDTO;
use BC\Api\Endpoint\AEndpoint;
use BC\Api\Exception\NotFoundException;
use BC\Exception\UnprocessableEntityException;
use BC\Modules\Blog\Api\DataBuilder\Tag\ITagDataBuilder;
use BC\Modules\Blog\Api\DTO\TagDTO;
use BC\Modules\Blog\Api\DTO\TagRowDTO;
use BC\Modules\Blog\Core\Action\DTO\CreateTagRequest;
use BC\Modules\Blog\Core\Action\DTO\SaveTagRequest;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;
use BC\Modules\Blog\Core\Action\Tag\ICreateTagAction;
use BC\Modules\Blog\Core\Action\Tag\ISaveTagAction;
use BC\Modules\Blog\Model\Tag as TagModel;
use Runway\Exception\Exception;
use Runway\Singleton\Container;

class Tag extends AEndpoint {
    public function __construct(
        private readonly ITagDataBuilder $dataBuilder
    ) {
    }

    /**
     * @return ListResponseDTO<TagRowDTO>
     *
     * @throws BadRequestException
     */
    #[API\Endpoint(path: 'tags', method: 'GET')]
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
        int $perPage = 25
    ): ListResponseDTO {
        if (!$sortBy) {
            $sortBy = 'count';
        }

        if (!$sortDir) {
            $sortDir = 'DESC';
        }

        $qb = TagModel::getQueryBuilder();

        $this->addFilter($qb, $filter, ['title', 'slug']);
        $total = $this->setSortLimitAndGetTotal(
            $qb,
            $sortBy,
            $sortDir,
            $page,
            $perPage,
            ['title', 'slug', 'count']
        );
        $qb->select('*, (SELECT COUNT(*) FROM `{post_tags}` WHERE tag_id = `{tags}`.id) AS count');

        return $this->handleWithException(
            fn () => new ListResponseDTO(
                items: array_map(
                    fn (array $tag): TagRowDTO => $this->dataBuilder->buildRow($tag),
                    $qb->getResults()
                ),
                total: $total
            )
        );
    }

    /**
     * @throws NotFoundException
     */
    #[API\Endpoint(path: 'tag', method: 'GET')]
    public function getOne(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id
    ): TagDTO {
        return $this->getEntity(
            TagModel::class,
            $id,
            'Тэг #{{id}} не найден.',
            fn (TagModel $tag): TagDTO => $this->dataBuilder->buildEntity($tag),
        );
    }

    /**
     * @throws UnprocessableEntityException
     */
    #[API\Endpoint(path: 'tag', method: 'POST')]
    public function createTag(
        #[API\Parameter(source: 'body', name: 'title')]
        string $title,
        #[API\Parameter(source: 'body', name: 'slug')]
        string $slug,
        #[API\Parameter(source: 'body', name: 'description')]
        string $description
    ): CreatedDTO {
        $action = Container::getInstance()->getService(ICreateTagAction::class);

        try {
            $response = $action->run(
                new CreateTagRequest(
                    name: $title,
                    slug: $slug,
                    description: $description
                )
            );
        } catch (ActionValidationException $e) {
            throw new UnprocessableEntityException(
                $e->getErrors(),
                'Ошибки при создании тэга'
            );
        } catch (Exception $e) {
            throw new RuntimeInternalErrorException($e->getMessage(), $e);
        }

        return new CreatedDTO(
            $response->tag->getId()
        );
    }

    /**
     * @throws UnprocessableEntityException
     */
    #[API\Endpoint(path: 'tag', method: 'PUT')]
    public function updateTag(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id,
        #[API\Parameter(source: 'body', name: 'title')]
        string $title,
        #[API\Parameter(source: 'body', name: 'slug')]
        string $slug,
        #[API\Parameter(source: 'body', name: 'description')]
        string $description
    ): SuccessfulResultDTO {
        $action = Container::getInstance()->getService(ISaveTagAction::class);

        try {
            $action->run(
                new SaveTagRequest(
                    id: $id,
                    name: $title,
                    slug: $slug,
                    description: $description
                )
            );
        } catch (ActionValidationException $e) {
            throw new UnprocessableEntityException(
                $e->getErrors(),
                'Ошибки при сохранении тэга'
            );
        } catch (Exception $e) {
            throw new RuntimeInternalErrorException($e->getMessage(), $e);
        }

        return new SuccessfulResultDTO();
    }

    #[API\Endpoint(path: 'tags', method: 'DELETE')]
    public function deleteTags(
        #[API\Parameter(source: 'body', name: 'rows')]
        array $rows
    ): SuccessfulResultDTO {
        $this->deleteEntities(TagModel::class, $rows);

        return new SuccessfulResultDTO();
    }
}
