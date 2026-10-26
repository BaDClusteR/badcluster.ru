<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Api\DataBuilder\Tag;

use BC\Core\Helper\MixedValue;
use BC\Modules\Blog\Api\DTO\TagDTO;
use BC\Modules\Blog\Api\DTO\TagRowDTO;
use BC\Modules\Blog\Model\Tag;

class TagDataBuilder implements ITagDataBuilder {
    public function buildRow(array $tag): TagRowDTO {
        return new TagRowDTO(
            id: MixedValue::toInt($tag['id'] ?? 0),
            title: MixedValue::toString($tag['title'] ?? ''),
            slug: MixedValue::toString($tag['slug'] ?? ''),
            count: MixedValue::toInt($tag['count'] ?? 0)
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
