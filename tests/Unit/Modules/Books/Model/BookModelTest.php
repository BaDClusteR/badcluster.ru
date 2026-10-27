<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Books\Model;

use BC\Modules\Books\Controller\Books;
use BC\Modules\Books\Model\Book;
use BC\Modules\Books\Model\BookFormat;
use BC\Modules\Books\Model\Chapter;
use BC\Tests\Fixture\Books\BooksFixture;
use DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Runway\Singleton\Container;

/**
 * The formats of a book in the database: the rows of the formats, the dumps, the download.
 */
#[CoversClass(Book::class)]
#[CoversClass(BookFormat::class)]
#[CoversClass(Books::class)]
final class BookModelTest extends TestCase {
    protected function setUp(): void {
        BooksFixture::createTables();
    }

    public function testMissingFormatsAreCreatedNotAllowed(): void {
        $other = BooksFixture::createBook(slug: 'other');
        new BookFormat()->setBook($other)->setType('txt')->setAllowed(true)->setFilename('other.txt')
            ->setDateGenerated(new DateTime())->persist();
        $book = BooksFixture::createBook();
        new BookFormat()->setBook($book)->setType('fb2')->setAllowed(true)->setFilename('doom.fb2')
            ->setDateGenerated(new DateTime())->persist();

        $formats = $book->getFormats();

        self::assertSame(['txt', 'fb2'], array_map(static fn(BookFormat $format): string => $format->getType(), $formats));
        self::assertFalse($formats[0]->getAllowed());
        self::assertSame('', $formats[0]->getFilename());
        self::assertSame(0, $formats[0]->getSize());
        self::assertSame('', $formats[0]->getDump());
        self::assertSame($book->getId(), $formats[0]->getBook()?->getId());
        self::assertTrue($formats[1]->getAllowed());
        self::assertSame('doom.fb2', $formats[1]->getFilename());

        // The created rows are reused: no duplicates.
        self::assertCount(2, $book->getFormats());
        self::assertCount(2, BookFormat::find(['book' => $book]));
        self::assertCount(1, BookFormat::find(['book' => $book, 'type' => 'txt']));
    }

    public function testSetFormatUpdatesStoredOne(): void {
        $other = BooksFixture::createBook(slug: 'other');
        $other->setFormat(new BookFormat()->setType('txt')->setAllowed(true)->setFilename('other.txt')->setDateGenerated(new DateTime()));
        $book = BooksFixture::createBook();
        $book->setFormat(new BookFormat()->setType('txt')->setAllowed(true)->setFilename('a.txt')->setDump('A')->setSize(1)
            ->setDateGenerated(new DateTime()));
        $book->setFormat(new BookFormat()->setType('txt')->setAllowed(false)->setFilename('b.txt')->setDump('BB')->setSize(2)
            ->setDateGenerated(new DateTime()));

        $stored = BookFormat::find(['book' => $book]);

        self::assertCount(1, $stored);
        self::assertFalse($stored[0]->getAllowed());
        self::assertSame('b.txt', $stored[0]->getFilename());
        self::assertSame('BB', $stored[0]->getDump());
        self::assertSame(2, $stored[0]->getSize());
        self::assertSame('other.txt', BookFormat::findOne(['book' => $other])?->getFilename());
    }

    public function testGenerateFormats(): void {
        $book = BooksFixture::createBook();
        BooksFixture::createChapter($book, 'Глава', [BooksFixture::paragraph('Текст')], 1, added: '2020-01-01');
        new BookFormat()->setBook($book)->setType('txt')->setAllowed(true)->setDump('old')->setSize(3)
            ->setDateGenerated(new DateTime('2000-01-01'))->persist();
        new BookFormat()->setBook($book)->setType('fb2')->setAllowed(false)->setDump('old')->setSize(3)
            ->setDateGenerated(new DateTime('2000-01-01'))->persist();

        $book->generateFormats();

        $txt = BookFormat::findOne(['book' => $book, 'type' => 'txt']);
        $fb2 = BookFormat::findOne(['book' => $book, 'type' => 'fb2']);
        self::assertNotNull($txt);
        self::assertNotNull($fb2);
        self::assertStringStartsWith("Doom\n", $txt->getDump());
        self::assertSame(strlen($txt->getDump()), $txt->getSize());
        self::assertGreaterThan(time() - 60, $txt->getDateGenerated()->getTimestamp());
        self::assertSame('', $fb2->getDump());
        self::assertSame(0, $fb2->getSize());
        self::assertGreaterThan(time() - 60, $fb2->getDateGenerated()->getTimestamp());
    }

    public function testFormatsOfBookWithoutPublishedChaptersAreCleared(): void {
        $book = BooksFixture::createBook();
        BooksFixture::createChapter($book, 'Черновик', [BooksFixture::paragraph('Текст')], 1, published: false);
        new BookFormat()->setBook($book)->setType('txt')->setAllowed(true)->setDump('old')->setSize(3)
            ->setDateGenerated(new DateTime('2000-01-01'))->persist();

        $book->generateFormats();

        $txt = BookFormat::findOne(['book' => $book, 'type' => 'txt']);
        self::assertNotNull($txt);
        self::assertSame('', $txt->getDump());
        self::assertSame(0, $txt->getSize());
    }

    public function testDumpOfUnknownFormatIsCleared(): void {
        $book = BooksFixture::createBook();
        $format = new BookFormat()->setBook($book)->setType('pdf')->setDump('old')->setSize(3)->setDateGenerated(new DateTime('2000-01-01'));

        $format->generateDump();

        self::assertSame('', $format->getDump());
        self::assertSame(0, $format->getSize());
        self::assertTrue($format->isPersistent());
    }

    public function testChapters(): void {
        $book = BooksFixture::createBook();
        BooksFixture::createChapter($book, 'Третья', [], 3);
        BooksFixture::createChapter($book, 'Первая', [], 1, published: false);
        BooksFixture::createChapter($book, 'Вторая', [], 2);
        BooksFixture::createChapter(BooksFixture::createBook(slug: 'other'), 'Чужая', [], 0);

        $titles = static fn(array $chapters): array => array_map(static fn(mixed $chapter): string => $chapter instanceof Chapter ? $chapter->getTitle() : '', $chapters);

        self::assertSame(['Первая', 'Вторая', 'Третья'], $titles($book->getChapters()));
        self::assertSame(['Вторая', 'Третья'], $titles($book->getChapters(true)));
    }

    public function testFb2IdIsCreatedOnce(): void {
        $book = BooksFixture::createBook();
        $id = $book->getOrCreateFb2Id();

        self::assertNotSame('', $id);
        self::assertSame($id, $book->getOrCreateFb2Id());
        self::assertSame($id, Book::findByUniqueIdentifier($book->getId())?->getFb2Id());

        $book->setFb2Id('fixed');
        self::assertSame('fixed', $book->getOrCreateFb2Id());
    }

    public function testBumpLastUpdateDate(): void {
        $book = BooksFixture::createBook();

        $book->bumpLastUpdateDate();

        $stored = Book::findByUniqueIdentifier($book->getId());
        self::assertNotNull($stored);
        self::assertEqualsWithDelta(time(), $stored->getLastUpdateDate()->getTimestamp(), 60);
    }

    public function testDownloadOfStoredDump(): void {
        $book = BooksFixture::createBook();
        new BookFormat()->setBook($book)->setType('txt')->setAllowed(true)->setFilename('doom.txt')->setDump('Text')
            ->setSize(4)->setDateGenerated(new DateTime())->persist();
        // The stored size goes to Content-Length.
        new BookFormat()->setBook($book)->setType('fb2')->setAllowed(true)->setFilename('doom.fb2')->setDump('Dump')
            ->setSize(5)->setDateGenerated(new DateTime())->persist();

        $response = $this->getController()->download('doom', 'fb2');

        self::assertSame(200, $response->getCode());
        self::assertSame('Dump', $response->getBody());
        self::assertSame('application/octet-stream', $response->getHeaders()['Content-Type'] ?? null);
        self::assertSame('attachment; filename="doom.fb2"', $response->getHeaders()['Content-Disposition'] ?? null);
        self::assertSame(5, (int)($response->getHeaders()['Content-Length'] ?? 0));
    }

    public function testDownloadGeneratesMissingDump(): void {
        $book = BooksFixture::createBook();
        BooksFixture::createChapter($book, 'Глава', [BooksFixture::paragraph('Текст')], 1, added: '2020-01-01');
        new BookFormat()->setBook($book)->setType('txt')->setAllowed(true)->setFilename('doom.txt')
            ->setDateGenerated(new DateTime())->persist();

        $response = $this->getController()->download('doom', 'txt');

        self::assertSame(200, $response->getCode());
        self::assertStringStartsWith("Doom\n", $response->getBody());
        self::assertStringContainsString('  Текст', $response->getBody());
        self::assertSame(strlen($response->getBody()), (int)($response->getHeaders()['Content-Length'] ?? 0));
        self::assertSame($response->getBody(), BookFormat::findOne(['type' => 'txt'])?->getDump());
    }

    private function getController(): Books {
        return Container::getInstance()->getService(Books::class);
    }
}
