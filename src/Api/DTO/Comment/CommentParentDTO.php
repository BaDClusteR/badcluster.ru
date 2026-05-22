<?php

namespace BC\Api\DTO\Comment;

readonly class CommentParentDTO {
    public function __construct(
        public int $id,
        public string $title,
        public string $text,
        public string $link
    ) {
    }
}
