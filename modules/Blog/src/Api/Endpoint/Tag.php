<?php

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
    /**
     * @return ListResponseDTO<TagRowDTO>
     *
     * @throws BadRequestException
     */
    #[API\Endpoint(path: 'post_tags', method: 'GET')]
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
        $total = $this->setSortLimitAndGetTotal($qb, $sortBy, $sortDir, $page, $perPage);
        $qb->select('*, (SELECT COUNT(*) FROM `{post_tags}` WHERE tag_id = `{tags}`.id) AS count');

        return $this->handleWithException(
            fn () => new ListResponseDTO(
                items: array_map(
                    fn (array $tag): TagRowDTO => $this->convertListResponseItem($tag),
                    $qb->getResults()
                ),
                total: $total
            )
        );
    }

    /**
     * @throws NotFoundException
     */
    #[API\Endpoint(path: 'post_tag', method: 'GET')]
    public function getOne(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id
    ): TagDTO {
        /** @var TagModel|null $tag */
        $tag = $this->handleWithException(
            static fn () => TagModel::findByUniqueIdentifier($id)
        );

        if (!$tag) {
            throw new NotFoundException("Тэг #$id не найден.");
        }

        return $this->handleWithException(
            fn (): TagDTO => $this->convertDetailedModel($tag)
        );
    }

    /**
     * @throws UnprocessableEntityException
     */
    #[API\Endpoint(path: 'post_tags', method: 'POST')]
    public function createTag(
        #[API\Parameter(source: 'body', name: 'title')]
        string $title,
        #[API\Parameter(source: 'body', name: 'slug')]
        string $slug
    ): CreatedDTO {
        $action = Container::getInstance()->getService(ICreateTagAction::class);

        try {
            $response = $action->run(
                new CreateTagRequest(
                    name: $title,
                    slug: $slug
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
    #[API\Endpoint(path: 'post_tags', method: 'PUT')]
    public function updateTag(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id,
        #[API\Parameter(source: 'body', name: 'title')]
        string $title,
        #[API\Parameter(source: 'body', name: 'slug')]
        string $slug
    ): SuccessfulResultDTO {
        $action = Container::getInstance()->getService(ISaveTagAction::class);

        try {
            $action->run(
                new SaveTagRequest(
                    id: $id,
                    name: $title,
                    slug: $slug
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

    #[API\Endpoint(path: 'post_tags', method: 'DELETE')]
    public function deleteTags(
        #[API\Parameter(source: 'body', name: 'rows')]
        array $rows
    ): SuccessfulResultDTO {
        $qb = TagModel::getQueryBuilder();

        $this->handleWithException(
            static fn () => $qb->delete()
                               ->where($qb->expr()->in('id', $rows))
                               ->execute()
        );

        return new SuccessfulResultDTO();
    }

    private function convertDetailedModel(TagModel $post): TagDTO {
        return new TagDTO(
            title: $post->getTitle(),
            slug: $post->getSlug()
        );
    }

    private function convertListResponseItem(array $tag): TagRowDTO {
        return new TagRowDTO(
            id: (int) ($tag['id'] ?? 0),
            title: (string) ($tag['title'] ?? ''),
            slug: (string) ($tag['slug'] ?? ''),
            count: (int) ($tag['count'] ?? 0)
        );
    }

    protected function getSortableColumns(): array {
        return ['title', 'slug', 'count'];
    }
}
