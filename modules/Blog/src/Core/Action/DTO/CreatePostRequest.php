<?php

namespace BC\Modules\Blog\Core\Action\DTO;

use BC\Model\Media;
use BC\Modules\Blog\Model\Tag;
use DateTime;

readonly class CreatePostRequest
{
    /**
     * @param Tag[] $tags
     */
    public function __construct(
        public string $title,
        public string $shortTitle,
        public string $annotation,
        public array $content,
        public string $slug,
        public string $metaDescription,
        public bool $published,
        public DateTime $publishDate,
        public DateTime $updateDate,
        public ?Media $coverImage,
        public string $coverImageAltText,
        public array $tags
    ) {
    }
}
