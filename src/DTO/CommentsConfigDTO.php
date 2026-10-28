<?php

declare(strict_types=1);

namespace BC\DTO;

use BC\Core\DTO\CommentDTO;

readonly class CommentsConfigDTO {
    /**
     * @param CommentDTO[] $comments
     */
    public function __construct(
        public array $comments,
        public string $emptyPhrase,
        public string $pageType,
        public string $pageId
    ) {
    }
}
