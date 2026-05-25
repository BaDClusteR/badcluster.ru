<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Provider;

use BC\DTO\PulseItemDTO;
use BC\Modules\Blog\Model\Post;

class PulseItemsProvider extends \BC\Provider\PulseItemsProvider {
    public function getPulseItemsUnsorted(): array {
        $items = parent::getPulseItemsUnsorted();

        $post = Post::findOne(['published' => true], ['publishDate', 'DESC']);

        if (
            ($annotation = $post?->getAnnotation())
            && ($image = $post->getCover())
        ) {
            $items[] = new PulseItemDTO(
                title: $post->getShortTitle() ?: $post->getTitle(),
                url: $post->getUrl(),
                tag: 'Блог',
                text: $annotation,
                image: $image,
                position: 500
            );
        }

        return $items;
    }
}
