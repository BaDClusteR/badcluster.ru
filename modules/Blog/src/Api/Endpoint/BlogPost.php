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
use BC\Model\Media;
use BC\Modules\Blog\Api\DataBuilder\Post\IBlogPostDataBuilder;
use BC\Modules\Blog\Api\DTO\BlogPostDTO;
use BC\Modules\Blog\Api\DTO\BlogPostRowDTO;
use BC\Modules\Blog\Core\Action\DTO\CreatePostRequest;
use BC\Modules\Blog\Core\Action\DTO\GetPostRequest;
use BC\Modules\Blog\Core\Action\DTO\SavePostRequest;
use BC\Modules\Blog\Core\Action\Post\ICreatePostAction;
use BC\Modules\Blog\Core\Action\Post\IGetPostAction;
use BC\Modules\Blog\Core\Action\Post\ISavePostAction;
use BC\Modules\Blog\Model\Post;
use BC\Modules\Blog\Model\Tag;
use Runway\Singleton\Container;

#[Docs\Group('Blog posts')]
class BlogPost extends AEndpoint {
    public function __construct(
        private readonly IDateConverter $dateConverter,
        private readonly IBlogPostDataBuilder $dataBuilder
    ) {
    }

    /**
     * @return ListResponseDTO<BlogPostRowDTO>
     *
     * @throws BadRequestException
     */
    #[API\Endpoint(path: 'posts', method: 'GET')]
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
                qb: Post::getQueryBuilder()->orderBy('publish_date', 'DESC'),
                filter: $filter,
                columnsToFind: ['title'],
                sortBy: $sortBy,
                sortDir: $sortDir,
                page: $page,
                perPage: $perPage,
                sortableColumns: ['title', 'slug', 'published', 'publish_date']
            ),
            fn (Post $post): BlogPostRowDTO => $this->dataBuilder->buildRow($post)
        );
    }

    /**
     * @throws NotFoundException
     */
    #[API\Endpoint(path: 'post', method: 'GET')]
    public function getOne(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id
    ): BlogPostDTO {
        $action = Container::getInstance()->getService(IGetPostAction::class);

        $post = $this->handleWithException(
            static fn () => $action->run(
                new GetPostRequest($id)
            )->post
        );

        return $this->handleWithException(
            fn (): BlogPostDTO => $this->dataBuilder->buildEntity($post)
        );
    }

    /**
     * @throws UnprocessableEntityException
     */
    #[API\Endpoint(path: 'post', method: 'POST')]
    public function createPost(
        #[API\Parameter(source: 'body', name: 'title')]
        string $title,
        #[API\Parameter(source: 'body', name: 'content')]
        array $content,
        #[API\Parameter(source: 'body', name: 'publishDate')]
        string $publishDate,
        #[API\Parameter(source: 'body', name: 'slug')]
        string $slug,
        #[API\Parameter(source: 'body', name: 'shortTitle')]
        string $shortTitle = '',
        #[API\Parameter(source: 'body', name: 'annotation')]
        string $annotation = '',
        #[API\Parameter(source: 'body', name: 'published')]
        bool $published = false,
        #[API\Parameter(source: 'body', name: 'metaDescription')]
        string $metaDescription = '',
        #[API\Parameter(source: 'body', name: 'tags')]
        array $tags = [],
        #[API\Parameter(source: 'body', name: 'updateDate')]
        ?string $updateDate = null,
        #[API\Parameter(source: 'body', name: 'coverImage')]
        ?array $coverImage = null,
    ): CreatedDTO {
        $response = null;
        $request = $this->handleWithException(
            fn () => new CreatePostRequest(
                title: $title,
                shortTitle: $shortTitle,
                annotation: $annotation,
                content: $content,
                slug: $slug,
                metaDescription: $metaDescription,
                published: $published,
                publishDate: $this->dateConverter->toDateTime($publishDate),
                updateDate: $updateDate
                    ? $this->dateConverter->toDateTime($updateDate)
                    : null,
                coverImage: $this->findMedia($coverImage),
                coverImageAltText: (string) ($coverImage['alt'] ?? ''),
                tags: Tag::find([
                    'id' => $tags,
                ])
            )
        );

        $this->handleActionWithException(
            function () use (&$response, $request) {
                $action = Container::getInstance()->getService(ICreatePostAction::class);
                $response = $action->run($request);
            },
            'Ошибки при создании поста'
        );

        return new CreatedDTO(
            $response->post->getId()
        );
    }

    /**
     * @throws UnprocessableEntityException
     */
    #[API\Endpoint(path: 'post', method: 'PUT')]
    public function savePost(
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
        #[API\Parameter(source: 'body', name: 'shortTitle')]
        string $shortTitle = '',
        #[API\Parameter(source: 'body', name: 'annotation')]
        string $annotation = '',
        #[API\Parameter(source: 'body', name: 'published')]
        bool $published = false,
        #[API\Parameter(source: 'body', name: 'metaDescription')]
        string $metaDescription = '',
        #[API\Parameter(source: 'body', name: 'tags')]
        array $tags = [],
        #[API\Parameter(source: 'body', name: 'updateDate')]
        ?string $updateDate = null,
        #[API\Parameter(source: 'body', name: 'coverImage')]
        ?array $coverImage = null,
    ): SuccessfulResultDTO {
        $request = $this->handleWithException(
            fn () => new SavePostRequest(
                id: $id,
                title: $title,
                shortTitle: $shortTitle,
                annotation: $annotation,
                content: $content,
                slug: $slug,
                metaDescription: $metaDescription,
                published: $published,
                publishDate: $this->dateConverter->toDateTime($publishDate),
                updateDate: $updateDate
                    ? $this->dateConverter->toDateTime($updateDate)
                    : null,
                coverImage: $this->findMedia($coverImage),
                coverImageAltText: (string) ($coverImage['alt'] ?? ''),
                tags: Tag::find([
                    'id' => $tags,
                ])
            )
        );

        $this->handleActionWithException(
            function () use ($request) {
                $action = Container::getInstance()->getService(ISavePostAction::class);
                $action->run($request);
            },
            'Ошибки при сохранении поста'
        );

        return new SuccessfulResultDTO();
    }

    #[API\Endpoint(path: 'posts', method: 'DELETE')]
    public function deletePosts(
        #[API\Parameter(source: 'body', name: 'rows')]
        array $rows
    ): SuccessfulResultDTO {
        $this->deleteEntities(Post::class, $rows);

        return new SuccessfulResultDTO();
    }
}
