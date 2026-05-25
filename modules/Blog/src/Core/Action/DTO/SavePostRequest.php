<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Core\Action\DTO;

use BC\Model\Media;
use BC\Modules\Blog\Model\Tag;
use DateTime;

readonly class SavePostRequest {
    /**
     * @param Tag[] $tags
     */
    public function __construct(
        public int $id,
        public string $title,
        public string $shortTitle,
        public string $annotation,
        public array $content,
        public string $slug,
        public string $metaDescription,
        public bool $published,
        public DateTime $publishDate,
        public ?DateTime $updateDate,
        public ?Media $coverImage,
        public string $coverImageAltText,
        public array $tags
    ) {
    }
}
