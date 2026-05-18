<?php

namespace BC\DTO;

readonly class CommentsConfigDTO {
    public function __construct(
        public array $comments,
        public string $emptyPhrase,
        public string $pageType,
        public string $pageId
    ) {
    }
}
