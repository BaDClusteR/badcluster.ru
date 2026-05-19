<?php

namespace BC\Widget\Common;

use BC\Core\DTO\CommentDTO;
use BC\Core\Trait\DateConverterTrait;
use BC\Widget\AWidget;

class Comment extends AWidget {
    use DateConverterTrait;

    protected function getTemplatePath(): string {
        return 'common/comment.phtml';
    }

    protected function getComment(): ?CommentDTO {
        return (($this->context['comment'] ?? null) instanceof CommentDTO)
            ? $this->context['comment']
            : null;
    }

    protected function getLevel(): int {
        return (int) ($this->context['level'] ?? 0);
    }

    protected function getCommentDateIso(): string {
        $date = $this->getComment()?->date;
        if (!$date) {
            return '';
        }

        return $this->getDateConverter()->toIsoFormat($date);
    }

    protected function getCommentDateHumanReadable(): string {
        $date = $this->getComment()?->date;

        if (!$date) {
            return '';
        }

        return $this->getDateConverter()->toFullForm(
            $date->getTimestamp(),
            false
        );
    }

    protected function getCommentDateRelative(): string {
        $date = $this->getComment()?->date;

        if (!$date) {
            return '';
        }

        return $this->getDateConverter()->toRelativeForm($date);
    }

    protected function prepareCommentText(string $text): string {
        $lines = explode("\n", $text);
        return '<p>' . implode('</p><p>', $lines) . '</p>';
    }
}
