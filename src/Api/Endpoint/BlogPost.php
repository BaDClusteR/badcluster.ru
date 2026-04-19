<?php

namespace BC\Api\Endpoint;

use ApiPlatform\Attribute as API;
use ApiPlatform\Attribute\Docs;
use ApiPlatform\Exception\BadRequestException;
use ApiPlatform\Exception\RuntimeInternalErrorException;
use BC\Api\DTO\BlogPostDTO;
use BC\Api\DTO\BlogPostsDTO;
use BC\Api\Enum\BlogPostStatusEnum;
use BC\Core\Converter\IConverter;
use BC\Model\Post;
use Throwable;

#[Docs\Group("Blog posts")]
readonly class BlogPost
{
    public function __construct(
        private IConverter $converter,
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

    private function convertModel(Post $post): BlogPostDTO {
        return new BlogPostDTO(
            id: $post->getId(),
            title: $post->getTitle(),
            slug: $post->getSlug(),
            published: $post->getPublished(),
            publishDate: $post->getPublished()
                ? $this->converter->convertTimestampToDateString(
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

    private function handleWithException(callable $handler): mixed {
        try {
            return $handler();
        } catch (Throwable $e) {
            throw new RuntimeInternalErrorException($e->getMessage(), $e);
        }
    }
}
