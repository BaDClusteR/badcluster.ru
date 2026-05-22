<?php

namespace BC\Modules\Blog\Api\Endpoint;

use ApiPlatform\Attribute as API;
use ApiPlatform\Attribute\Docs;
use ApiPlatform\Exception\BadRequestException;
use BC\Api\DTO\CreatedDTO;
use BC\Api\DTO\ListResponseDTO;
use BC\Api\DTO\SuccessfulResultDTO;
use BC\Api\Endpoint\AEndpoint;
use BC\Api\Exception\NotFoundException;
use BC\Core\Converter\IDateConverter;
use BC\Core\Converter\Media\IMediaConverter;
use BC\Core\Helper\IBlockHelper;
use BC\Exception\UnprocessableEntityException;
use BC\Model\Media;
use BC\Modules\Blog\Api\DTO\BlogPostDTO;
use BC\Modules\Blog\Api\DTO\BlogPostRowDTO;
use BC\Modules\Blog\Api\DTO\BlogPostTagDTO;
use BC\Modules\Blog\Api\DTO\BlogPostTagsDTO;
use BC\Modules\Blog\Core\Action\DTO\CreatePostRequest;
use BC\Modules\Blog\Core\Action\DTO\GetPostRequest;
use BC\Modules\Blog\Core\Action\DTO\SavePostRequest;
use BC\Modules\Blog\Core\Action\Post\ICreatePostAction;
use BC\Modules\Blog\Core\Action\Post\IGetPostAction;
use BC\Modules\Blog\Core\Action\Post\ISavePostAction;
use BC\Modules\Blog\Model\Post;
use BC\Modules\Blog\Model\Tag;
use Runway\Exception\Exception;
use Runway\Singleton\Container;

#[Docs\Group('Blog posts')]
class BlogPost extends AEndpoint {
    public function __construct(
        private readonly IDateConverter $dateConverter,
        private readonly IMediaConverter $mediaConverter,
        private readonly IBlockHelper $blockHelper
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
        int $perPage = 25
    ): ListResponseDTO {
        $qb = Post::getQueryBuilder()->orderBy('publish_date', 'DESC');

        $this->addFilter($qb, $filter, ['title']);
        $total = $this->setSortLimitAndGetTotal($qb, $sortBy, $sortDir, $page, $perPage);


        return $this->handleWithException(
            fn () => new ListResponseDTO(
                items: array_map(
                    fn (Post $post): BlogPostRowDTO => $this->buildListResponseItem($post),
                    $qb->getEntities()
                ),
                total: $total
            )
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
            fn (): BlogPostDTO => $this->convertDetailedModel($post)
        );
    }

    #[API\Endpoint(path: 'tags', method: 'GET')]
    public function getTags(): BlogPostTagsDTO {
        /** @var Tag[] $tags */
        $tags = $this->handleWithException(
            static fn () => Tag::find()
        );

        return $this->convertTags($tags);
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
                coverImage: $this->getCover($coverImage),
                coverImageAltText: (string) ($coverImage['alt'] ?? ''),
                tags: Tag::find([
                    'id' => $tags
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
                coverImage: $this->getCover($coverImage),
                coverImageAltText: (string) ($coverImage['alt'] ?? ''),
                tags: Tag::find([
                    'id' => $tags
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

    private function getCover(?array $coverImage): ?Media {
        return $coverImage === null
            ? null
            : $this->handleWithException(
                fn () => Media::findByUniqueIdentifier(
                    (int) ($coverImage['id'] ?? 0)
                )
            );
    }

    /**
     * @param Tag[] $tags
     */
    private function convertTags(array $tags): BlogPostTagsDTO {
        return new BlogPostTagsDTO(
            tags: array_map(
                fn (Tag $tag): BlogPostTagDTO => $this->convertTag($tag),
                $tags
            )
        );
    }

    private function convertTag(Tag $tag): BlogPostTagDTO {
        return new BlogPostTagDTO(
            id: $tag->getId(),
            title: $tag->getTitle()
        );
    }

    /**
     * @throws Exception
     */
    private function convertDetailedModel(Post $post): BlogPostDTO {
        $updateDate = $post->getUpdateDate()?->getTimestamp();

        return new BlogPostDTO(
            id: $post->getId(),
            title: $post->getTitle(),
            shortTitle: $post->getShortTitle(),
            metaDescription: $post->getMetaDescription(),
            annotation: $post->getAnnotation(),
            coverImage: $this->mediaConverter->convertMedia(
                $post->getCover()
            )?->toArray(),
            publishDate: $this->dateConverter->toPickerValue(
                $post->getPublishDate()
            ),
            updateDate: $updateDate
                ? $this->dateConverter->toPickerValue($updateDate)
                : null,
            content: $this->blockHelper->enrichBlocks(
                $post->getContent()
            ),
            published: $post->getPublished(),
            slug: $post->getSlug(),
            tags: array_map(
                static fn (Tag $tag): string => (string) $tag->getId(),
                $post->getTags()
            )
        );
    }

    private function buildListResponseItem(Post $post): BlogPostRowDTO {
        $isPublished = $post->getPublished();

        return new BlogPostRowDTO(
            id: $post->getId(),
            title: $post->getShortTitle() ?: $post->getTitle(),
            slug: $post->getSlug(),
            published: $isPublished,
            publishDate: $isPublished
                ? $this->dateConverter->toShortForm($post->getPublishDate())
                : '—',
            updateDate: ($isPublished && $post->getUpdateDate())
                ? $this->dateConverter->toShortForm($post->getUpdateDate())
                : ''
        );
    }

    /**
     * @return string[]
     */
    protected function getSortableColumns(): array {
        return ['title', 'slug', 'published', 'publishDate'];
    }
}
