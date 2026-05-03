<?php

namespace BC\Api\Endpoint;

use ApiPlatform\Attribute as API;
use ApiPlatform\Attribute\Docs;
use ApiPlatform\Exception\BadRequestException;
use BC\Api\DTO\BlogPostDetailedDTO;
use BC\Api\DTO\BlogPostDTO;
use BC\Api\DTO\BlogPostsDTO;
use BC\Api\DTO\BlogPostTagDTO;
use BC\Api\DTO\BlogPostTagsDTO;
use BC\Api\Exception\NotFoundException;
use BC\Core\Converter\IConverter;
use BC\Model\Post;
use BC\Model\Tag;

#[Docs\Group("Blog posts")]
class BlogPost extends AEndpoint
{
    public function __construct(
        private readonly IConverter $converter
    ) {
    }

    /**
     * @throws BadRequestException
     */
    #[API\Endpoint(path: "posts", method: "GET")]
    #[Docs\Endpoint("Get the list of blog posts")]
    public function getList(
        #[API\Parameter(source: "query")]
        #[Docs\Argument(example: "contact", description: "Posts filter")]
        string $filter = '',

        #[API\Parameter(source: "query")]
        #[Docs\Argument(example: "title", description: "Column to sort for")]
        string $sortBy = '',

        #[API\Parameter(source: "query")]
        #[Docs\Argument(example: "DESC", description: "Sort direction")]
        string $sortDir = '',

        #[API\Parameter(source: "query")]
        #[Docs\Argument(example: 2, description: "Results page")]
        int $page = 1,

        #[API\Parameter(source: "query")]
        #[Docs\Argument(example: 10, description: "Results count on the page")]
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
            fn() => new BlogPostsDTO(
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
    #[API\Endpoint(path: "post", method: "GET")]
    public function getOne(
        #[API\Parameter(source: "path", name: "identifier")]
        int $id
    ): BlogPostDetailedDTO {
        $post = $this->handleWithException(
            static fn() => Post::findByUniqueIdentifier($id)
        );

        if (!$post) {
            throw new NotFoundException("Post #$id not found");
        }

        return $this->convertDetailedModel($post);
    }

    #[API\Endpoint(path: "tags", method: "GET")]
    public function getTags(): BlogPostTagsDTO {
        /** @var Tag[] $tags */
        $tags = $this->handleWithException(
            static fn() => Tag::find()
        );

        return $this->convertTags($tags);
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

    private function convertDetailedModel(Post $post): BlogPostDetailedDTO {
        $updateDate = $post->getUpdateDate()?->getTimestamp();

        return new BlogPostDetailedDTO(
            id: $post->getId(),
            title: $post->getTitle(),
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
            content: $post->getContent(),
            published: $post->getPublished(),
            slug: $post->getSlug()
        );
    }

    private function convertModel(Post $post): BlogPostDTO {
        return new BlogPostDTO(
            id: $post->getId(),
            title: $post->getTitle(),
            slug: $post->getSlug(),
            published: $post->getPublished(),
            publishDate: $post->getPublished()
                ? $this->converter->convertTimestampToHumanReadableDate(
                    $post->getPublishDate()->getTimestamp()
                )
                : "—",
            publishTime: $post->getPublished()
                ? $this->converter->convertTimestampToTimeString(
                    $post->getPublishDate()->getTimestamp()
                )
                : ""
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

        return in_array($sortDirection, ["ASC", "DESC"], true)
            ? $sortDirection
            : $this->getDefaultSortDirection();
    }

    private function getDefaultSortDirection(): string {
        return "ASC";
    }
}
