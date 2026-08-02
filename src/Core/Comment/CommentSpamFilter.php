<?php

declare(strict_types=1);

namespace BC\Core\Comment;

use BC\Model\Config;

class CommentSpamFilter implements ICommentSpamFilter {
    public const string CONFIG_NAME = 'comment_blacklist';

    /**
     * Строка, начинающаяся с решётки, — заметка для себя, а не слово для фильтрации
     */
    private const string COMMENT_PREFIX = '#';

    public function isSpam(string $nickname, string $comment): bool {
        $blacklist = $this->getBlacklist();

        if (!$blacklist) {
            return false;
        }

        // Ник проверяем вместе с текстом: ссылку спамеры нередко суют именно в имя
        $haystack = mb_strtolower($nickname . "\n" . $comment);

        foreach ($blacklist as $word) {
            if (str_contains($haystack, mb_strtolower($word))) {
                return true;
            }
        }

        return false;
    }

    public function getBlacklist(): array {
        $lines = preg_split('/\R/u', Config::getConfig(self::CONFIG_NAME)) ?: [];

        return array_values(
            array_filter(
                array_map(trim(...), $lines),
                static fn (string $line): bool => $line !== ''
                    && !str_starts_with($line, self::COMMENT_PREFIX)
            )
        );
    }
}
