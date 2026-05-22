<?php

namespace BC\Api\DTO\Comment;

readonly class CommentRowDTO {
    public function __construct(
        public int $id,
        public string $date,
        public string $name,
        public string $comment,
        public string $status,
        public string $page,
        public string $pageLink
    ) {
    }
}
