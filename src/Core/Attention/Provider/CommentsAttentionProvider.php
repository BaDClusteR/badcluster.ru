<?php

declare(strict_types=1);

namespace BC\Core\Attention\Provider;

use BC\Core\Attention\DTO\AttentionItemDTO;
use BC\Core\Attention\Enum\AttentionSeverityEnum;
use BC\Core\Attention\IAttentionItemsProvider;
use BC\Core\Helper\RussianPlural;
use BC\Model\Comment;

class CommentsAttentionProvider implements IAttentionItemsProvider {
    public function getItems(): array {
        $count = Comment::getQueryBuilder()
            ->where('status = :status')
            ->setVariable('status', Comment::STATUS_ON_MODERATION)
            ->count();

        if (!$count) {
            return [];
        }

        return [
            new AttentionItemDTO(
                message: sprintf(
                    '%d %s %s модерации',
                    $count,
                    RussianPlural::form($count, 'комментарий', 'комментария', 'комментариев'),
                    RussianPlural::form($count, 'ждёт', 'ждут', 'ждут')
                ),
                severity: AttentionSeverityEnum::Warning,
                adminPath: 'comments'
            ),
        ];
    }
}
