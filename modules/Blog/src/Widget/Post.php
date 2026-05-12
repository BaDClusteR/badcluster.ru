<?php

namespace BC\Modules\Blog\Widget;

use BC\Core\Trait\ConverterTrait;
use BC\Modules\Blog\Model\Post as PostModel;
use BC\Modules\Blog\Widget\Block\Paragraph;
use BC\Widget\AWidget;
use DateTime;
use Runway\Exception\RuntimeException;

class Post extends AWidget
{
    use ConverterTrait;

    protected ?PostModel $post = null {
        get {
            return $this->post;
        }
    }

    protected function applyContext(array $context): void
    {
        parent::applyContext($context);

        if (!$this->post && !(($context['post'] ?? null) instanceof PostModel)) {
            throw new RuntimeException(__METHOD__ . ": post is not set or not an instance of " . PostModel::class);
        }

        $this->post ??= $context['post'];
    }

    protected function getTemplatePath(): string {
        return 'modules/Blog/post.phtml';
    }

    protected function getTitle(): string {
        return $this->post->getTitle();
    }

    protected function getDateTime(DateTime $dt): string {
        return $dt->format('Y-m-d');
    }

    protected function getHumanReadableDateTime(DateTime $dt): string {
        return $this->getConverter()->convertTimestampToHumanReadableDate(
            $dt->getTimestamp(),
        );
    }

    protected function renderBlock(string $type, array $data): string {
        $widget = match($type) {
            'paragraph' => new Paragraph($data),
            default => null
        };

        return (string)$widget?->render();
    }
}
