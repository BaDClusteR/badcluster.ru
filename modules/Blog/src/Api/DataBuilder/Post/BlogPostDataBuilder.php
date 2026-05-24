<?php

namespace BC\Modules\Blog\Api\DataBuilder\Post;

use BC\Core\Converter\IDateConverter;
use BC\Core\Converter\Media\IMediaConverter;
use BC\Core\Helper\IBlockHelper;
use BC\Modules\Blog\Api\DTO\BlogPostDTO;
use BC\Modules\Blog\Api\DTO\BlogPostRowDTO;
use BC\Modules\Blog\Model\Post;
use BC\Modules\Blog\Model\Tag;

readonly class BlogPostDataBuilder implements IBlogPostDataBuilder {
    public function __construct(
        private IDateConverter $dateConverter,
        private IMediaConverter $mediaConverter,
        private IBlockHelper $blockHelper
    ) {
    }

    public function buildRow(Post $post): BlogPostRowDTO {
        $isPublished = $post->getPublished();

        return new BlogPostRowDTO(
            id: $post->getId(),
            title: $post->getShortTitle() ?: $post->getTitle(),
            slug: $post->getSlug(),
            published: $isPublished,
            publish_date: $isPublished
                ? $this->dateConverter->toShortForm($post->getPublishDate())
                : '—',
            updateDate: ($isPublished && $post->getUpdateDate())
                ? $this->dateConverter->toShortForm($post->getUpdateDate())
                : ''
        );
    }

    public function buildEntity(Post $post): BlogPostDTO {
        $updateDate = $post->getUpdateDate()?->getTimestamp();

        return new BlogPostDTO(
            id: $post->getId(),
            title: $post->getTitle(),
            shortTitle: $post->getShortTitle(),
            metaDescription: $post->getMetaDescription(),
            annotation: $post->getAnnotation(),
            coverImage: $this->mediaConverter->convertMedia(
                $post->getCover()
            )?->toArray(),
            publishDate: $this->dateConverter->toPickerValue(
                $post->getPublishDate()
            ),
            updateDate: $updateDate
                ? $this->dateConverter->toPickerValue($updateDate)
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
}
