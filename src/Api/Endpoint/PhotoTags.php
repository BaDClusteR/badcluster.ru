<?php

declare(strict_types=1);

namespace BC\Api\Endpoint;

use ApiPlatform\Attribute as API;
use ApiPlatform\Attribute\Docs;
use BC\Api\DTO\Photo\PhotoTagDTO;
use BC\Api\DTO\Photo\PhotoTagsDTO;
use BC\Model\PhotoTag;

#[Docs\Group('Photos')]
class PhotoTags extends AEndpoint {
    #[API\Endpoint(path: 'photo_tags', method: 'GET')]
    public function getTags(): PhotoTagsDTO {
        /** @var PhotoTag[] $tags */
        $tags = $this->handleWithException(
            static fn () => PhotoTag::find(orderBy: 'id')
        );

        return new PhotoTagsDTO(
            tags: array_map(
                static fn (PhotoTag $tag): PhotoTagDTO => new PhotoTagDTO(
                    id: $tag->getId(),
                    title: $tag->getTitle()
                ),
                $tags
            )
        );
    }
}