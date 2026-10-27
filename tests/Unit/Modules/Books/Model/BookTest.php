<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Books\Model;

use BC\Modules\Books\Model\Book;
use BC\Modules\Books\Model\BookFormat;
use BC\Modules\Books\Model\Chapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The logic of the models without the database.
 */
#[CoversClass(Book::class)]
#[CoversClass(BookFormat::class)]
#[CoversClass(Chapter::class)]
final class BookTest extends TestCase {
    public function testType(): void {
        $book = new Book();
        self::assertFalse($book->isTranslation());
        self::assertFalse($book->isAuteur());

        $book->setType(Book::TYPE_TRANSLATION);
        self::assertTrue($book->isTranslation());
        self::assertFalse($book->isAuteur());

        $book->setType(Book::TYPE_AUTEUR);
        self::assertFalse($book->isTranslation());
        self::assertTrue($book->isAuteur());
    }

    public function testAuthorsOfTranslation(): void {
        $book = new Book()->setType(Book::TYPE_TRANSLATION)->setAuthor(' Dafydd ab Hugh ,Brad Linaweaver');

        self::assertSame(['Dafydd ab Hugh', 'Brad Linaweaver'], $book->getAuthors());
    }

    public function testAuthorOfAuteurBook(): void {
        $book = new Book()->setType(Book::TYPE_AUTEUR)->setAuthor('Somebody Else');

        self::assertSame(['BaD ClusteR'], $book->getAuthors());
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('provideHistory')]
    public function testFb2HistoryLines(string $history, array $expected): void {
        self::assertSame($expected, new Book()->setFb2History($history)->getFb2HistoryLines());
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function provideHistory(): iterable {
        yield 'empty' => ['', []];
        yield 'one line' => ['1.0 — первая версия', ['1.0 — первая версия']];
        yield 'line breaks of any kind, trimmed, empty skipped' => [
            "  1.0 — первая\r\n\n\t\r1.1 — вторая  \n   \n",
            ['1.0 — первая', '1.1 — вторая'],
        ];
    }

    public function testDefaultFb2Version(): void {
        self::assertSame('1.0', Book::DEFAULT_FB2_VERSION);
        self::assertSame(Book::DEFAULT_FB2_VERSION, new Book()->getFb2Version());
    }

    public function testUuidIsVersion4(): void {
        $uuids = [];

        for ($i = 0; $i < 50; $i++) {
            $uuid = Book::generateUuid();
            self::assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid);
            $uuids[] = $uuid;
        }

        self::assertCount(50, array_unique($uuids));
    }

    /**
     * The version and the variant bits are set on the random bytes 6 and 8 themselves: their other bits stay random
     * (the chance of a false failure is below 1e-30).
     */
    public function testUuidKeepsRandomBitsOfVersionAndVariantBytes(): void {
        $uuids = array_map(static fn(): string => Book::generateUuid(), range(1, 400));
        $chars = static fn(int $position): array => array_values(array_unique(array_map(
            static fn(string $uuid): string => $uuid[$position],
            $uuids
        )));
        $differ = static fn(int $a, int $b): bool => array_any($uuids, static fn(string $uuid): bool => $uuid[$a] !== $uuid[$b]);

        // The low half of the byte 6 (after "4"): even and odd values.
        $versionLow = $chars(15);
        self::assertTrue(array_any($versionLow, static fn(string $char): bool => hexdec($char) % 2 === 1));
        self::assertTrue(array_any($versionLow, static fn(string $char): bool => hexdec($char) % 2 === 0));
        self::assertSame(['4'], $chars(14));

        // The variant: 10xx, all four values.
        $variant = $chars(19);
        sort($variant);
        self::assertSame(['8', '9', 'a', 'b'], $variant);
        self::assertTrue(array_any($chars(20), static fn(string $char): bool => hexdec($char) % 2 === 1));

        // Not copied from the neighbouring bytes 5, 7 and 9.
        self::assertTrue($differ(15, 12));
        self::assertTrue($differ(15, 17));
        self::assertTrue($differ(20, 17));
        self::assertTrue($differ(20, 22));
    }

    public function testUrls(): void {
        $book = new Book()->setSlug('doom');
        $chapter = new Chapter()->setBook($book)->setSlug('chapter-1');
        $format = new BookFormat()->setBook($book)->setFilename('doom.fb2');

        self::assertSame('http://bc.test/books/doom', $book->getUrl());
        self::assertSame('http://bc.test/books/doom/chapter-1', $chapter->getUrl());
        self::assertSame('http://bc.test/books/doom.fb2', $format->getUrl());
    }

    public function testPublicTitleOfChapter(): void {
        $chapter = new Chapter()->setTitle('Глава 1');

        self::assertSame('[Черновик] Глава 1', $chapter->getPublicTitle());
        self::assertSame('Глава 1', $chapter->setPublished(true)->getPublicTitle());
    }
}
