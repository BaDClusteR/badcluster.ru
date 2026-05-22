<?php

namespace BC\Modules\Blog\Api\Endpoint;

use BC\Modules\Blog\Model\Post;

class Comments extends \BC\Api\Endpoint\Comments {
    protected function getPageTitle(string $pageType, int $pageId): string {
        if ($pageType === 'post') {
            /** @var Post|null $post */
            $post = $this->handleWithException(
                static fn () => Post::findByUniqueIdentifier($pageId)
            );

            if ($post) {
                return 'Пост "' . ($post->getShortTitle() ?: $post->getTitle()) . '"';
            }

            return '';
        }

        return parent::getPageTitle($pageType, $pageId);
    }

    protected function getPageLink(string $pageType, int $pageId): string {
        if ($pageType === 'post') {
            /** @var Post|null $post */
            $post = $this->handleWithException(
                static fn () => Post::findByUniqueIdentifier($pageId)
            );

            return $post
                ? "/admin/blog/{$post->getId()}"
                : '';
        }

        return parent::getPageLink($pageType, $pageId);
    }
}
