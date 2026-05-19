<?php

namespace BC\Core\Action\DTO;

use BC\Core\DTO\CommentDTO;

readonly class GetCommentsResponse {
    /**
     * @param CommentDTO[] $comments
     */
    public function __construct(
        public array $comments,
    ) {
    }
}
