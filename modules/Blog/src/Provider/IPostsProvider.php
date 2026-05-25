<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Provider;

interface IPostsProvider {
    public function getPosts(string $tag, int $page, bool $onlyPublished): ?iterable;

    public function getTotalPostsCount(string $tag, bool $onlyPublished): int;

    public function getShowBy(): int;
}
