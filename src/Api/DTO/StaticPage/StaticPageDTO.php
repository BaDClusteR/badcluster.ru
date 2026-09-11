<?php

declare(strict_types=1);

namespace BC\Api\DTO\StaticPage;

readonly class StaticPageDTO {
    public function __construct(
        public string $title,
        public string $shortTitle,
        public array $content,
        public string $slug,
        public bool $published,
        public bool $indexable,
        public bool $textBlock,
        public string $publishDate,
        public string $metaDescription,
        public string $backLinkText,
        public string $backLinkUrl
    ) {
    }
}
