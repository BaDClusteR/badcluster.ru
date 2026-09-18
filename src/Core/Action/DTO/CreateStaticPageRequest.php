<?php

declare(strict_types=1);

namespace BC\Core\Action\DTO;

use DateTime;

readonly class CreateStaticPageRequest {
    public function __construct(
        public string $title,
        public string $shortTitle,
        public array $content,
        public string $slug,
        public bool $published,
        public bool $indexable,
        public bool $textBlock,
        public ?DateTime $publishDate,
        public string $metaDescription,
        public string $backLinkText,
        public string $backLinkUrl
    ) {
    }
}
