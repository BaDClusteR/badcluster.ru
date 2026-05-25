<?php

declare(strict_types=1);

namespace BC\Api\DataBuilder\Comment;

use BC\Api\DTO\Comment\CommentDTO;
use BC\Api\DTO\Comment\CommentRowDTO;
use BC\Model\Comment;

interface ICommentDataBuilder {
    public function buildRow(Comment $comment): CommentRowDTO;

    public function buildEntity(Comment $comment): CommentDTO;
}
