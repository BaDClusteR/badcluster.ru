<?php

namespace BC\Modules\Blog\Api\Endpoint;

use ApiPlatform\Attribute as API;
use ApiPlatform\Attribute\Docs;
use ApiPlatform\Exception\BadRequestException;
use ApiPlatform\Exception\RuntimeInternalErrorException;
use BC\Api\Endpoint\AEndpoint;
use BC\Api\Exception\NotFoundException;
use BC\Core\Converter\IConverter;
use BC\Core\Converter\Media\IMediaConverter;
use BC\Core\Helper\IBlockHelper;
use BC\Exception\UnprocessableEntityException;
use BC\Model\Media;
use BC\Modules\Blog\Api\DTO\BlogPostCreatedDTO;
use BC\Modules\Blog\Api\DTO\BlogPostDetailedDTO;
use BC\Modules\Blog\Api\DTO\BlogPostDTO;
use BC\Modules\Blog\Api\DTO\BlogPostsDTO;
use BC\Modules\Blog\Api\DTO\BlogPostTagDTO;
use BC\Modules\Blog\Api\DTO\BlogPostTagsDTO;
use BC\Modules\Blog\Api\DTO\SuccessfulResultDTO;
use BC\Modules\Blog\Core\Action\DTO\CreatePostRequest;
use BC\Modules\Blog\Core\Action\DTO\GetPostRequest;
use BC\Modules\Blog\Core\Action\DTO\SavePostRequest;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;
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
        private readonly IConverter $converter,
        private readonly IMediaConverter $mediaConverter,
        private readonly IBlockHelper $blockHelper
    ) {
    }

    /**
     * @throws BadRequestException
     */
    #[API\Endpoint(path: 'posts', method: 'GET')]
    #[Docs\Endpoint('Get the list of blog posts')]
    public function getList(
        #[API\Parameter(source: 'query')]
        #[Docs\Argument(example: 'contact', description: 'Posts filter')]
        string $filter = '',
        #[API\Parameter(source: 'query')]
        #[Docs\Argument(example: 'title', description: 'Column to sort for')]
        string $sortBy = '',
        #[API\Parameter(source: 'query')]
        #[Docs\Argument(example: 'DESC', description: 'Sort direction')]
        string $sortDir = '',
        #[API\Parameter(source: 'query')]
        #[Docs\Argument(example: 2, description: 'Results page')]
        int $page = 1,
        #[API\Parameter(source: 'query')]
        #[Docs\Argument(example: 10, description: 'Results count on the page')]
        int $perPage = 25
    ): BlogPostsDTO {
        $filter = strtolower(trim($filter));
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));

        $qb = Post::getQueryBuilder()
                  ->orderBy('publish_date', 'DESC')
                  ->setLimit($perPage, ($page - 1) * $perPage);

        if ($filter !== '') {
            $qb = $qb->andWhere('LOWER(title) LIKE :filter')
                ->setVariable('filter', "%$filter%");
        }

        if ($sortBy && !in_array($sortBy, $this->getSortableColumns())) {
            throw new BadRequestException(
                sprintf("Не могу сортировать по '%s'.", $sortBy)
            );
        }

        if (in_array($sortBy, $this->getSortableColumns())) {
            $qb = $qb->orderBy(
                $sortBy,
                $this->sanitizeSortDirection($sortDir)
            );
        }

        return $this->handleWithException(
            fn () => new BlogPostsDTO(
                posts: array_map(
                    fn (Post $post): BlogPostDTO => $this->convertModel($post),
                    $qb->getEntities()
                )
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
    ): BlogPostDetailedDTO {
        $action = Container::getInstance()->getService(IGetPostAction::class);

        $post = $this->handleWithException(
            static fn () => $action->run(
                new GetPostRequest($id)
            )->post
        );

        return $this->handleWithException(
            fn (): BlogPostDetailedDTO => $this->convertDetailedModel($post)
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
    ): BlogPostCreatedDTO {
        $action = Container::getInstance()->getService(ICreatePostAction::class);

        try {
            $response = $action->run(
                new CreatePostRequest(
                    title: $title,
                    shortTitle: $shortTitle,
                    annotation: $annotation,
                    content: $content,
                    slug: $slug,
                    metaDescription: $metaDescription,
                    published: $published,
                    publishDate: $this->converter->convertDateTimeStringToDateTime($publishDate),
                    updateDate: $updateDate
                        ? $this->converter->convertDateTimeStringToDateTime($updateDate)
                        : null,
                    coverImage: $this->getCover($coverImage),
                    coverImageAltText: (string) ($coverImage['alt'] ?? ''),
                    tags: Tag::find([
                        'id' => $tags
                    ])
                )
            );
        } catch (ActionValidationException $e) {
            throw new UnprocessableEntityException(
                $e->getErrors(),
                'Ошибки при создании поста'
            );
        } catch (Exception $e) {
            throw new RuntimeInternalErrorException($e->getMessage(), $e);
        }

        return new BlogPostCreatedDTO(
            id: $response->post->getId()
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
        $action = Container::getInstance()->getService(ISavePostAction::class);

        try {
            $action->run(
                new SavePostRequest(
                    id: $id,
                    title: $title,
                    shortTitle: $shortTitle,
                    annotation: $annotation,
                    content: $content,
                    slug: $slug,
                    metaDescription: $metaDescription,
                    published: $published,
                    publishDate: $this->converter->convertDateTimeStringToDateTime($publishDate),
                    updateDate: $updateDate
                        ? $this->converter->convertDateTimeStringToDateTime($updateDate)
                        : null,
                    coverImage: $this->getCover($coverImage),
                    coverImageAltText: (string) ($coverImage['alt'] ?? ''),
                    tags: Tag::find([
                        'id' => $tags
                    ])
                )
            );
        } catch (ActionValidationException $e) {
            throw new UnprocessableEntityException(
                $e->getErrors(),
                'Ошибки при сохранении поста'
            );
        } catch (Exception $e) {
            throw new RuntimeInternalErrorException($e->getMessage(), $e);
        }

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
    private function convertDetailedModel(Post $post): BlogPostDetailedDTO {
        $updateDate = $post->getUpdateDate()?->getTimestamp();

        return new BlogPostDetailedDTO(
            id: $post->getId(),
            title: $post->getTitle(),
            shortTitle: $post->getShortTitle(),
            metaDescription: $post->getMetaDescription(),
            annotation: $post->getAnnotation(),
            coverImage: $this->mediaConverter->convertMedia(
                $post->getCover()
            )?->toArray(),
            createdDate: $this->converter->convertTimestampToDateTimeString(
                $post->getCreatedDate()->getTimestamp()
            ),
            publishDate: $this->converter->convertTimestampToDateTimeString(
                $post->getPublishDate()->getTimestamp()
            ),
            updateDate: $updateDate
                ? $this->converter->convertTimestampToDateTimeString(
                    $updateDate
                )
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

    private function convertModel(Post $post): BlogPostDTO {
        return new BlogPostDTO(
            id: $post->getId(),
            title: $post->getShortTitle() ?: $post->getTitle(),
            slug: $post->getSlug(),
            published: $post->getPublished(),
            publishDate: $post->getPublished()
                ? $this->converter->convertTimestampToHumanReadableDate(
                    $post->getPublishDate()->getTimestamp()
                )
                : '—',
            publishTime: $post->getPublished()
                ? $this->converter->convertTimestampToTimeString(
                    $post->getPublishDate()->getTimestamp()
                )
                : ''
        );
    }

    /**
     * @return string[]
     */
    private function getSortableColumns(): array {
        return ['title', 'slug', 'published', 'publishDate'];
    }

    private function sanitizeSortDirection(string $sortDirection): string {
        $sortDirection = strtoupper(trim($sortDirection));

        return in_array($sortDirection, ['ASC', 'DESC'], true)
            ? $sortDirection
            : $this->getDefaultSortDirection();
    }

    private function getDefaultSortDirection(): string {
        return 'ASC';
    }
}
