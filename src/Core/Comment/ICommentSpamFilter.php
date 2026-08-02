<?php

declare(strict_types=1);

namespace BC\Core\Comment;

interface ICommentSpamFilter {
    /**
     * Проверяет комментарий по чёрному списку слов из настройки comment_blacklist.
     */
    public function isSpam(string $nickname, string $comment): bool;

    /**
     * @return string[] Слова чёрного списка: по одному на строку, пустые строки и комментарии отброшены
     */
    public function getBlacklist(): array;
}
