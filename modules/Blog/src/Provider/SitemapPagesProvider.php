<?php

namespace BC\Modules\Blog\Provider;

use BC\DTO\SitemapEntryDTO;
use BC\Modules\Blog\Model\Post;
use BC\Modules\Blog\Model\Tag;
use BC\Provider\ISitemapPagesProvider;
use Runway\Exception\Exception;

class SitemapPagesProvider implements ISitemapPagesProvider {

    /**
     * @inheritDoc
     */
    public function getSitemapPages(): array {
        $result = [
            new SitemapEntryDTO('/blog')
        ];

        try {
            /** @var Tag $tag */
            foreach (Tag::iterate() as $tag) {
                $result[] = new SitemapEntryDTO($tag->getUrl());
            }

            /** @var Post $post */
            foreach (Post::iterate(['published' => true]) as $post) {
                $result[] = new SitemapEntryDTO($post->getUrl());
            }
        } catch (Exception) {
        } finally {
            return $result;
        }
    }
}
