<?php

namespace BC\Modules\Blog\Provider;

use BC\DTO\SitemapEntryDTO;
use BC\Modules\Blog\Model\Note;
use BC\Modules\Blog\Model\Post;
use BC\Modules\Blog\Model\Tag;
use BC\Provider\ISitemapPagesProvider;
use Runway\Exception\Exception;

readonly class SitemapPagesProvider implements ISitemapPagesProvider {

    public function __construct(
        private INotesProvider $notesProvider
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getSitemapPages(): array {
        $result = [
            new SitemapEntryDTO('/blog')
        ];

        if ($this->notesProvider->hasNotes()) {
            $result[] = new SitemapEntryDTO('/notes');
        }

        try {
            /** @var Tag $tag */
            foreach (Tag::iterate() as $tag) {
                $result[] = new SitemapEntryDTO($tag->getUrl());
            }

            /** @var Post $post */
            foreach (Post::iterate(['published' => true]) as $post) {
                $result[] = new SitemapEntryDTO($post->getUrl());
            }

            foreach ($this->notesProvider->getNotes(true) as $note) {
                $result[] = new SitemapEntryDTO($note->getUrl());
            }
        } catch (Exception) {
        } finally {
            return $result;
        }
    }
}
