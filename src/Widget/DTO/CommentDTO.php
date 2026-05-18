<?php

namespace BC\Widget\DTO;

use DateTime;

readonly class CommentDTO {
    /**
     * @param CommentDTO[] $children
     */
    public function __construct(
        public string $name,
        public string $comment,
        public DateTime $date,
        public array $children = []
    ) {
    }
}
