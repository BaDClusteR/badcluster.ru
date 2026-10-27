<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Books\Api;

use BC\Core\Converter\DateConverter;
use BC\Core\Formatter\Formatter;
use BC\Modules\Books\Api\DataBuilder\Book\BookDataBuilder;
use BC\Modules\Books\Api\DataBuilder\Chapter\ChapterDataBuilder;
use BC\Modules\Books\Model\Book;
use BC\Modules\Books\Model\BookFormat;
use BC\Tests\Fixture\Books\BooksFixture;
use DateTime;
use DateTimeZone;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BookDataBuilder::class)]
#[CoversClass(ChapterDataBuilder::class)]
final class DataBuilderTest extends TestCase {
    private BookDataBuilder $bookBuilder;

    protected function setUp(): void {
        BooksFixture::createTables();
        $this->bookBuilder = new BookDataBuilder(new DateConverter('Europe/Samara'), new Formatter());
    }

    public function testBookRow(): void {
        $book = BooksFixture::createBook();
        BooksFixture::createChapter($book, 'Первая', [], 1);
        BooksFixture::createChapter($book, 'Вторая', [], 2, published: false);
        BooksFixture::createChapter(BooksFixture::createBook(slug: 'other'), 'Чужая', [], 1);

        $row = $this->bookBuilder->buildRow($book);

        self::assertSame($book->getId(), $row->id);
        self::assertNull($row->cover);
        self::assertSame('Doom', $row->title);
        self::assertSame('Коротко.', $row->shortAnnotation);
        self::assertSame(Book::TYPE_AUTEUR, $row->type);
        self::assertSame('4 мар 2025', $row->lastUpdateDate);
        self::assertSame('2 главы', $row->chapterCount);
    }

    public function testBookRowWithCover(): void {
        $book = BooksFixture::createBook();
        $cover = BooksFixture::createMedia('image/jpeg', 'cover.jpg', width: 100);
        $book->setCover($cover)->persist();

        $row = $this->bookBuilder->buildRow($book);

        self::assertSame($cover->getId(), $row->cover?->id);
        self::assertSame('0 глав', $this->bookBuilder->buildRow(BooksFixture::createBook(slug: 'empty'))->chapterCount);
    }

    public function testBookEntity(): void {
        $book = BooksFixture::createBook(type: Book::TYPE_TRANSLATION, author: 'Author');
        $book->setGroup('Серия')->setPosition(7)->setFb2Genre('sf')->setFb2Id('id-1')->setFb2Version('1.1')
            ->setFb2History('history')->persist();
        new BookFormat()->setBook($book)->setType('fb2')->setAllowed(true)->setFilename('doom.fb2')->setSize(2048)
            ->setPostfix('<p>post</p>')->setDateGenerated(new DateTime('2025-01-02 03:04:00', new DateTimeZone('UTC')))->persist();

        $dto = $this->bookBuilder->buildEntity($book);

        self::assertSame('doom', $dto->slug);
        self::assertNull($dto->cover);
        self::assertNull($dto->coverBg);
        self::assertSame('Doom', $dto->title);
        self::assertSame('Author', $dto->author);
        self::assertSame('Аннотация.', $dto->annotation);
        self::assertSame('Коротко.', $dto->shortAnnotation);
        self::assertSame(Book::TYPE_TRANSLATION, $dto->type);
        self::assertSame('2025-03-04T09:06:07+04:00', $dto->lastUpdateDate);
        self::assertSame('Серия', $dto->group);
        self::assertSame(7, $dto->position);
        self::assertSame('sf', $dto->fb2Genre);
        self::assertSame('id-1', $dto->fb2Id);
        self::assertSame('1.1', $dto->fb2Version);
        self::assertSame('history', $dto->fb2History);

        self::assertSame(['txt', 'fb2'], array_keys($dto->formats));
        $fb2 = $dto->formats['fb2'];
        self::assertSame('fb2', $fb2->type);
        self::assertTrue($fb2->allowed);
        self::assertSame('doom.fb2', $fb2->filename);
        self::assertSame(2048, $fb2->size);
        self::assertSame('2 Кб', $fb2->sizeHumanReadable);
        self::assertSame('2 января 2025г. в 07:04', $fb2->dateGenerated);
        self::assertSame('<p>post</p>', $fb2->postfix);
        self::assertFalse($dto->formats['txt']->allowed);
        self::assertSame('0 байт', $dto->formats['txt']->sizeHumanReadable);
    }

    public function testBookEntityWithoutGroup(): void {
        $book = BooksFixture::createBook();

        self::assertNull($book->getGroup());
        self::assertSame('', $this->bookBuilder->buildEntity($book)->group);
    }

    public function testChapter(): void {
        $book = BooksFixture::createBook();
        $chapter = BooksFixture::createChapter($book, 'Глава', [BooksFixture::paragraph('Текст')], 3, added: '2024-12-31 22:00:00', part: 'Часть');
        $chapter->setUpdateDate(new DateTime('2025-02-03 10:00:00', new DateTimeZone('UTC')));
        $builder = new ChapterDataBuilder(new DateConverter('Europe/Samara'));

        $row = $builder->buildRow($chapter);
        self::assertSame($chapter->getId(), $row->id);
        self::assertSame('Глава', $row->title);
        self::assertSame('1 янв 2025', $row->addedDate);
        self::assertSame('3 фев 2025', $row->updateDate);
        self::assertTrue($row->published);

        $dto = $builder->buildEntity($chapter);
        self::assertSame('Глава', $dto->title);
        self::assertSame(['blocks' => [BooksFixture::paragraph('Текст')]], $dto->content);
        self::assertSame(3, $dto->position);
        self::assertTrue($dto->published);
        self::assertSame('2025-01-01T02:00:00+04:00', $dto->addedDate);
        self::assertSame('chapter-3', $dto->slug);
        self::assertSame('Часть', $dto->part);
    }
}
