<?php

namespace BC\Modules\Blog\Api\Endpoint;

use ApiPlatform\Attribute as API;
use BC\Api\Endpoint\AEndpoint;
use BC\Modules\Blog\Api\DTO\BlogPostTagDTO;
use BC\Modules\Blog\Api\DTO\BlogPostTagsDTO;
use BC\Modules\Blog\Model\Tag;

class BlogPostTags extends AEndpoint {
    #[API\Endpoint(path: 'post_tags', method: 'GET')]
    public function getTags(): BlogPostTagsDTO {
        /** @var Tag[] $tags */
        $tags = $this->handleWithException(
            static fn () => Tag::find()
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
}
