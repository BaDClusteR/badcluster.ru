<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Api\DataBuilder\Tag;

use BC\Modules\Blog\Api\DTO\TagDTO;
use BC\Modules\Blog\Api\DTO\TagRowDTO;
use BC\Modules\Blog\Model\Tag;

interface ITagDataBuilder {
    /**
     * @param array<string, mixed> $tag A row of the tags (with the count of the posts).
     */
    public function buildRow(array $tag): TagRowDTO;

    public function buildEntity(Tag $tag): TagDTO;
}
