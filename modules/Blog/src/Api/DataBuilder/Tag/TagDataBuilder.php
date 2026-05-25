<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Api\DataBuilder\Tag;

use BC\Modules\Blog\Api\DTO\TagDTO;
use BC\Modules\Blog\Api\DTO\TagRowDTO;
use BC\Modules\Blog\Model\Tag;

class TagDataBuilder implements ITagDataBuilder {
    public function buildRow(array $tag): TagRowDTO {
        return new TagRowDTO(
            id: (int) ($tag['id'] ?? 0),
            title: (string) ($tag['title'] ?? ''),
            slug: (string) ($tag['slug'] ?? ''),
            count: (int) ($tag['count'] ?? 0)
        );
    }

    public function buildEntity(Tag $tag): TagDTO {
        return new TagDTO(
            title: $tag->getTitle(),
            slug: $tag->getSlug(),
            description: $tag->getDescription()
        );
    }
}
