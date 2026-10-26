<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Provider;

use BC\Modules\Blog\Model\Post;

interface IPostsProvider {
    /**
     * @return iterable<Post>|null Null if there are no posts on the page (or on an error).
     */
    public function getPosts(string $tag, int $page, bool $onlyPublished): ?iterable;

    public function getTotalPostsCount(string $tag, bool $onlyPublished): int;

    public function getShowBy(): int;
}
