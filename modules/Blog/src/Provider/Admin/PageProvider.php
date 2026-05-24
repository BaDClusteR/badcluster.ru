<?php

namespace BC\Modules\Blog\Provider\Admin;

use BC\DTO\PageDTO;
use BC\Modules\Blog\Model\Post;
use BC\Provider\Admin\IPageProvider;
use Runway\Exception\Exception;

readonly class PageProvider implements IPageProvider {
    public function __construct(
        private IPageProvider $inner
    ) {
    }

    public function getPage(string $pageType, int $pageId): PageDTO {
        if ($pageType === 'post') {
            try {
                $post = Post::findByUniqueIdentifier($pageId);
                return $post
                    ? new PageDTO(
                        sprintf('Пост "%s"', $post->getShortTitle() ?: $post->getTitle()),
                        "/admin/blog/{$post->getId()}"
                    )
                    : new PageDTO('Неизвестный пост', '');
            } catch (Exception) {
                return new PageDTO('Неизвестный пост', '');
            }
        }

        return $this->inner->getPage($pageType, $pageId);
    }
}
