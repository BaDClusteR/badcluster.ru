<?php

declare(strict_types=1);

namespace BC\Tests\Fixture\Books;

use BC\Model\Media;
use BC\Modules\Books\Model\Book;
use BC\Modules\Books\Model\BookFormat;
use BC\Modules\Books\Model\Chapter;
use BC\Tests\Support\SqliteSchema;
use DateTime;
use DateTimeZone;

/**
 * The tables of the Books module and the books of the tests.
 */
final class BooksFixture {
    public static function createTables(): void {
        SqliteSchema::createModelTables(Book::class, BookFormat::class, Chapter::class, Media::class);
    }

    public static function createBook(
        string $title = 'Doom',
        string $type = Book::TYPE_AUTEUR,
        ?string $author = null,
        string $annotation = 'Аннотация.',
        string $slug = 'doom'
    ): Book {
        $book = new Book()
            ->setTitle($title)
            ->setSlug($slug)
            ->setType($type)
            ->setAuthor($author)
            ->setAnnotation($annotation)
            ->setShortAnnotation('Коротко.')
            ->setLastUpdateDate(new DateTime('2025-03-04 05:06:07', new DateTimeZone('UTC')));
        $book->persist();

        return $book;
    }

    /**
     * @param list<array{type: string, data: array<string, mixed>}> $blocks
     */
    public static function createChapter(
        Book $book,
        string $title,
        array $blocks,
        int $position,
        bool $published = true,
        string $added = '2020-01-01 12:00:00',
        string $part = ''
    ): Chapter {
        $chapter = new Chapter()
            ->setBook($book)
            ->setTitle($title)
            ->setSlug('chapter-' . $position)
            ->setContent(['blocks' => $blocks])
            ->setPosition($position)
            ->setPublished($published)
            ->setAddedDate(new DateTime($added, new DateTimeZone('UTC')))
            ->setUpdateDate(new DateTime($added, new DateTimeZone('UTC')))
            ->setPart($part);
        $chapter->persist();

        return $chapter;
    }

    /**
     * @return array{type: string, data: array<string, mixed>}
     */
    public static function paragraph(string $text, string $alignment = 'left'): array {
        return ['type' => 'paragraph', 'data' => ['text' => $text, 'alignment' => $alignment]];
    }

    /**
     * @return array{type: string, data: array<string, mixed>}
     */
    public static function delimiter(): array {
        return ['type' => 'delimiter', 'data' => []];
    }

    /**
     * @return array{type: string, data: array<string, mixed>}
     */
    public static function media(int $mediaId, string $caption = ''): array {
        return ['type' => 'media', 'data' => ['media' => ['id' => $mediaId], 'caption' => $caption]];
    }

    /**
     * A media row without a file: nothing reads or deletes the file in the tests.
     */
    public static function createMedia(string $mime, string $path, ?Media $parent = null, int $width = 0): Media {
        $media = new Media()
            ->setPath($path)
            ->setMime($mime)
            ->setWidth($width)
            ->setHeight($width)
            ->setSize(0)
            ->setAlt('')
            ->setParent($parent)
            ->setMd5('');
        $media->persist();

        return $media;
    }
}
