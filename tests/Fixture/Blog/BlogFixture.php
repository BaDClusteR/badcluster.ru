<?php

declare(strict_types=1);

namespace BC\Tests\Fixture\Blog;

use BC\Model\Media;
use BC\Modules\Blog\Model\Note;
use BC\Modules\Blog\Model\Post;
use BC\Modules\Blog\Model\PostTag;
use BC\Modules\Blog\Model\Tag;
use BC\Tests\Support\SqliteSchema;
use DateTime;
use DateTimeZone;

/**
 * The tables of the Blog module and the posts, notes and tags of the tests.
 */
final class BlogFixture {
    public static function createTables(): void {
        SqliteSchema::createModelTables(Post::class, PostTag::class, Tag::class, Note::class, Media::class);
    }

    public static function date(string $date): DateTime {
        return new DateTime($date, new DateTimeZone('UTC'));
    }

    public static function createPost(
        string $slug,
        bool $published = true,
        string $publishDate = '2025-01-01 10:00:00',
        string $title = '',
        ?Media $cover = null,
        string $annotation = ''
    ): Post {
        $post = new Post()
            ->setTitle($title ?: "Post $slug")
            ->setSlug($slug)
            ->setPublished($published)
            ->setCreatedDate(self::date('2024-12-31 00:00:00'))
            ->setPublishDate(self::date($publishDate))
            ->setContent(['blocks' => []])
            ->setAnnotation($annotation)
            ->setCover($cover);
        $post->persist();

        return $post;
    }

    public static function createTag(string $slug, string $title = ''): Tag {
        $tag = new Tag()->setSlug($slug)->setTitle($title ?: "Tag $slug")->setDescription("About $slug");
        $tag->persist();

        return $tag;
    }

    public static function createNote(string $slug, bool $published = true, string $publishDate = '2025-01-01 10:00:00'): Note {
        $note = new Note()
            ->setTitle("Note $slug")
            ->setSlug($slug)
            ->setPublished($published)
            ->setCreatedDate(self::date('2024-12-31 00:00:00'))
            ->setPublishDate(self::date($publishDate))
            ->setContent(['blocks' => []]);
        $note->persist();

        return $note;
    }

    /**
     * A media row without a file: nothing reads or deletes the file in the tests.
     */
    public static function createMedia(string $path, string $alt = ''): Media {
        $media = new Media()->setPath($path)->setMime('image/jpeg')->setWidth(10)->setHeight(20)->setSize(0)
            ->setAlt($alt)->setParent(null)->setMd5('');
        $media->persist();

        return $media;
    }
}
