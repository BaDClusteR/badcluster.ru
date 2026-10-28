<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Books\Format;

use BC\Modules\Books\Format\Fb2;
use BC\Modules\Books\Format\Txt;
use BC\Modules\Books\Model\Book;
use BC\Modules\Books\Model\BookFormat;
use BC\Modules\Books\Provider\BookFormat\BookFormatProvider;
use BC\Modules\Books\Provider\BookFormat\IBookFormatProvider;
use BC\Modules\Books\Widget\Format\ABook;
use BC\Tests\Fixture\Books\BooksFixture;
use BC\Tests\Fixture\Books\Fb2MediaProbe;
use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Runway\Singleton\Container;

/**
 * The downloadable formats of a book: TXT and FB2 (the widgets of Widget/Format), the chapters from SQLite.
 */
#[CoversClass(Txt::class)]
#[CoversClass(Fb2::class)]
#[CoversClass(ABook::class)]
#[CoversClass(\BC\Modules\Books\Widget\Format\Txt\Book::class)]
#[CoversClass(\BC\Modules\Books\Widget\Format\Txt\Blocks::class)]
#[CoversClass(\BC\Modules\Books\Widget\Format\Txt\Block\Paragraph::class)]
#[CoversClass(\BC\Modules\Books\Widget\Format\Txt\Block\Media::class)]
#[CoversClass(\BC\Modules\Books\Widget\Format\Fb2\Book::class)]
#[CoversClass(\BC\Modules\Books\Widget\Format\Fb2\Blocks::class)]
#[CoversClass(\BC\Modules\Books\Widget\Format\Fb2\Block\Media::class)]
#[CoversClass(BookFormatProvider::class)]
final class BookFormatsTest extends TestCase {
    private const string FB2_NS = 'http://www.gribuser.ru/xml/fictionbook/2.0';

    protected function setUp(): void {
        BooksFixture::createTables();
    }

    public function testTxt(): void {
        $book = $this->createBookWithChapters();

        self::assertSame(
            "Doom\n\n\n  Аннотация.\n\n\n Первая\n\n  Он и она[1].\n\n\n* * *\n\n\n\n"
            . "  [Видео: http://bc.test/static/media/clip.mp4]\n\n\n\n\n Часть 1\n\n\n\n Вторая\n\n"
            . "  Текст\nстрока[2]\n\n\n\nBaD ClusteR\n2019 — 2021, https://badcluster.ru\n\n\n Примечания\n\n"
            . "[1] — Примечание & ещё\n[2] — Ещё\n",
            new Txt()->generateBook($book)
        );
    }

    public function testTxtOfTranslation(): void {
        $book = BooksFixture::createBook(
            title: 'Knee-Deep in the Dead',
            type: Book::TYPE_TRANSLATION,
            author: 'Dafydd ab Hugh, Brad Linaweaver',
            annotation: "Первый абзац.\n\nВторой абзац.\n"
        );
        BooksFixture::createChapter($book, 'Глава', [BooksFixture::paragraph('&laquo;Текст&raquo;&nbsp;и&nbsp;<b>жирный</b> &gt; <br />дальше &quot;q&quot; &apos;a&apos;')], 1, added: '2022-01-01');

        self::assertSame(
            "Dafydd ab Hugh, Brad Linaweaver.\n\nKnee-Deep in the Dead\n\n\n  Первый абзац.\n  Второй абзац.\n\n\n Глава\n\n"
            . "  «Текст» и жирный > \nдальше \"q\" 'a'\n\n\n\nПеревел BaD ClusteR\n2022 — 2022, https://badcluster.ru",
            new Txt()->generateBook($book)
        );
    }

    public function testCustomPostfixOfFormat(): void {
        $book = BooksFixture::createBook();
        BooksFixture::createChapter($book, 'Глава', [BooksFixture::paragraph('Текст')], 1, added: '2018-01-01');
        BooksFixture::createChapter($book, 'Глава', [BooksFixture::paragraph('Текст')], 2, added: '2023-01-01');
        new BookFormat()->setBook($book)->setType('txt')->setPostfix('Конец, {{start_year}}–{{end_year}}, {{start_year}}')
            ->setDateGenerated(new \DateTime())->persist();
        new BookFormat()->setBook($book)->setType('fb2')->setPostfix('<p>FB2 {{end_year}}</p>')
            ->setDateGenerated(new \DateTime())->persist();

        self::assertStringEndsWith("\n\n\nКонец, 2018–2023, 2018", new Txt()->generateBook($book));
        self::assertStringContainsString('<p>FB2 2023</p>', new Fb2()->generateBook($book));
    }

    public function testEmptyParagraphsAndUnknownBlocksAreSkipped(): void {
        $book = BooksFixture::createBook();
        BooksFixture::createChapter($book, 'Глава', [
            BooksFixture::paragraph(' &nbsp; '),
            ['type' => 'unknown', 'data' => ['text' => 'nothing']],
            BooksFixture::media(999),
            BooksFixture::paragraph('Текст'),
        ], 1, added: '2020-01-01');

        self::assertStringContainsString(" Глава\n\n\n  Текст\n\n\n\nBaD ClusteR", new Txt()->generateBook($book));

        // In FB2 the empty paragraph is an empty line, the rest is skipped too.
        $xpath = $this->loadFb2(new Fb2()->generateBook($book));
        self::assertSame(['Текст'], $this->texts($xpath, '//f:body[not(@name)]/f:section[1]/f:p'));
        self::assertSame([''], $this->texts($xpath, '//f:body[not(@name)]/f:section[1]/f:empty-line'));
        self::assertSame([], $this->texts($xpath, '//f:image'));
    }

    public function testBookWithoutPublishedChaptersHasCurrentYear(): void {
        $book = BooksFixture::createBook();
        BooksFixture::createChapter($book, 'Черновик', [BooksFixture::paragraph('Скрыто')], 1, published: false);
        $year = date('Y');

        $txt = new Txt()->generateBook($book);

        self::assertStringNotContainsString('Скрыто', $txt);
        self::assertStringEndsWith("BaD ClusteR\n$year — $year, https://badcluster.ru", $txt);
        self::assertStringContainsString("$year - $year", new Fb2()->generateBook($book));
    }

    public function testFb2(): void {
        $book = $this->createBookWithChapters();
        $book->setFb2Genre('sf_horror')->setFb2Version('1.2')->setFb2History("1.0 — первая\n\n1.2 — <правки> & ошибки")->persist();

        $xpath = $this->loadFb2(new Fb2()->generateBook($book));

        self::assertSame('sf_horror', $this->text($xpath, '//f:title-info/f:genre'));
        self::assertSame('BaD ClusteR', $this->text($xpath, '//f:title-info/f:author/f:nickname'));
        self::assertSame('http://bc.test', $this->text($xpath, '//f:title-info/f:author/f:home-page'));
        self::assertSame('hi@badcluster.me', $this->text($xpath, '//f:title-info/f:author/f:email'));
        self::assertSame('Doom', $this->text($xpath, '//f:title-info/f:book-title'));
        self::assertSame('Аннотация.', $this->text($xpath, '//f:title-info/f:annotation/f:p'));
        self::assertSame('ru', $this->text($xpath, '//f:title-info/f:lang'));
        self::assertSame('ru', $this->text($xpath, '//f:title-info/f:src-lang'));
        self::assertSame([], $this->texts($xpath, '//f:translator'));
        self::assertSame([], $this->texts($xpath, '//f:coverpage'));
        self::assertSame([], $this->texts($xpath, '//f:binary'));

        self::assertSame('1.2', $this->text($xpath, '//f:document-info/f:version'));
        self::assertSame(['1.0 — первая', '1.2 — <правки> & ошибки'], $this->texts($xpath, '//f:document-info/f:history/f:p'));
        self::assertSame(date('Y-m-d'), $xpath->evaluate('string(//f:document-info/f:date/@value)'));

        // The id is generated once and kept in the book.
        $id = $this->text($xpath, '//f:document-info/f:id');
        self::assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $id);
        self::assertSame($id, Book::findByUniqueIdentifier($book->getId())?->getFb2Id());
        self::assertSame($id, $this->text($this->loadFb2(new Fb2()->generateBook($book)), '//f:document-info/f:id'));

        self::assertSame('Doom', $this->text($xpath, '//f:body[not(@name)]/f:title/f:p'));
        self::assertSame(
            ['Первая', 'Часть 1', 'Вторая'],
            $this->texts($xpath, '//f:body[not(@name)]/f:section/f:title/f:p')
        );
        self::assertSame(
            "Он и\u{00A0}она[1].",
            $this->text($xpath, '//f:body[not(@name)]/f:section[1]/f:p')
        );
        self::assertSame('#n1', $xpath->evaluate('string(//f:body[not(@name)]/f:section[1]/f:p/f:a/@l:href)'));
        self::assertSame('note', $xpath->evaluate('string(//f:body[not(@name)]/f:section[1]/f:p/f:a/@type)'));
        self::assertSame('* * *', $this->text($xpath, '//f:body[not(@name)]/f:section[1]/f:subtitle'));
        self::assertSame(['Текст', 'строка[2]'], $this->texts($xpath, '//f:body[not(@name)]/f:section[3]/f:p'));
        self::assertSame(
            ['BaD ClusteR', '2019 - 2021, https://badcluster.ru'],
            $this->texts($xpath, '//f:body[not(@name)]/f:section[4]/f:p')
        );

        self::assertSame('Примечания', $this->text($xpath, '//f:body[@name="notes"]/f:title/f:p'));
        self::assertSame(['n1', 'n2'], $this->texts($xpath, '//f:body[@name="notes"]/f:section/@id'));
        self::assertSame(['Примечание & ещё', 'Ещё'], $this->texts($xpath, '//f:body[@name="notes"]/f:section/f:p'));
    }

    public function testFb2OfTranslation(): void {
        $book = BooksFixture::createBook(type: Book::TYPE_TRANSLATION, author: 'Dafydd  ab   Hugh,  Linaweaver ');
        BooksFixture::createChapter($book, 'Глава', [BooksFixture::paragraph('Текст')], 1, added: '2022-01-01');

        $xpath = $this->loadFb2(new Fb2()->generateBook($book));

        self::assertSame(['Dafydd', 'Linaweaver'], $this->texts($xpath, '//f:title-info/f:author/f:first-name'));
        self::assertSame(['ab Hugh'], $this->texts($xpath, '//f:title-info/f:author/f:last-name'));
        self::assertSame([], $this->texts($xpath, '//f:title-info/f:author/f:nickname'));
        self::assertSame([], $this->texts($xpath, '//f:title-info/f:genre'));
        self::assertSame('en', $this->text($xpath, '//f:title-info/f:src-lang'));
        self::assertSame('BaD ClusteR', $this->text($xpath, '//f:title-info/f:translator/f:nickname'));
        self::assertSame([], $this->texts($xpath, '//f:history'));
        self::assertSame([], $this->texts($xpath, '//f:body[@name="notes"]'));
        self::assertSame(
            ['Перевел BaD ClusteR', '2022 - 2022, https://badcluster.ru'],
            $this->texts($xpath, '//f:body/f:section[2]/f:p')
        );
    }

    /**
     * The plain texts (the titles, the names, the annotation) are escaped: an "&" or a "<" made the file invalid XML.
     */
    public function testFb2EscapesPlainTexts(): void {
        $book = BooksFixture::createBook(
            title: 'Tom & Jerry <3',
            type: Book::TYPE_TRANSLATION,
            author: 'A&B <C>',
            annotation: 'Кошки & мышки <b>'
        );
        BooksFixture::createChapter($book, 'Глава "1" & <2>', [BooksFixture::paragraph('Текст')], 1, part: 'Часть & I');

        $xpath = $this->loadFb2(new Fb2()->generateBook($book));

        self::assertSame('Tom & Jerry <3', $this->text($xpath, '//f:title-info/f:book-title'));
        self::assertSame('Tom & Jerry <3', $this->text($xpath, '//f:body[not(@name)]/f:title/f:p'));
        self::assertSame(['A&B'], $this->texts($xpath, '//f:title-info/f:author/f:first-name'));
        self::assertSame('Кошки & мышки <b>', $this->text($xpath, '//f:title-info/f:annotation/f:p'));
        self::assertContains('Часть & I', $this->texts($xpath, '//f:body[not(@name)]//f:section/f:title/f:p'));
        self::assertContains('Глава "1" & <2>', $this->texts($xpath, '//f:body[not(@name)]//f:section/f:title/f:p'));
    }

    public function testFb2ImageIsReadFromFileOfThumbnail(): void {
        $image = BooksFixture::createMedia('image/png', 'picture.png', width: 3000);
        BooksFixture::createMedia('image/webp', 'picture-1500.webp', $image, 1500);
        BooksFixture::createMedia('image/jpeg', 'picture-1000.jpg', $image, 1000);

        $probe = new Fb2MediaProbe(['media' => ['id' => $image->getId()]]);

        self::assertTrue($probe->image());
        self::assertSame(PROJECT_ROOT . '/static/media/picture.png', $probe->imagePath());

        $thumbnail = BooksFixture::createMedia('image/jpeg', 'picture-1500.jpg', $image, 1500);
        $probe = new Fb2MediaProbe(['media' => ['id' => $image->getId()]]);

        self::assertSame($thumbnail->getLocalPath(), $probe->imagePath());
        self::assertSame(PROJECT_ROOT . '/static/media/picture-1500.jpg', $probe->imagePath());
    }

    public function testFb2MediaThatIsNotImage(): void {
        $video = BooksFixture::createMedia('video/mp4', 'clip.mp4');

        $probe = new Fb2MediaProbe(['media' => ['id' => $video->getId()]]);

        self::assertFalse($probe->image());
        self::assertSame('', $probe->imagePath());
        self::assertFalse(new Fb2MediaProbe()->image());
    }

    public function testFormatsOfProvider(): void {
        $provider = Container::getInstance()->getService(IBookFormatProvider::class);

        self::assertSame(['txt', 'fb2'], array_map(static fn($format): string => $format->type, $provider->getFormats()));
        self::assertInstanceOf(Txt::class, $provider->getFormats()[0]->format);
        self::assertInstanceOf(Txt::class, $provider->getFormat('txt'));
        self::assertInstanceOf(Fb2::class, $provider->getFormat('fb2'));
        self::assertNull($provider->getFormat('pdf'));
        self::assertSame('txt', new Txt()->getType());
        self::assertSame('fb2', new Fb2()->getType());
    }

    private function createBookWithChapters(): Book {
        $book = BooksFixture::createBook();
        $video = BooksFixture::createMedia('video/mp4', 'clip.mp4');
        BooksFixture::createChapter($book, 'Первая', [
            BooksFixture::paragraph('Он и она<sup>[Примечание &amp; ещё]</sup>.'),
            BooksFixture::delimiter(),
            BooksFixture::media($video->getId(), 'Подпись'),
        ], 1, added: '2019-05-05');
        BooksFixture::createChapter($book, 'Вторая', [BooksFixture::paragraph('Текст<br>строка<sup>[Ещё]</sup>')], 3, added: '2021-05-05', part: 'Часть 1');
        BooksFixture::createChapter($book, 'Черновик', [BooksFixture::paragraph('Скрыто')], 2, published: false, added: '2010-01-01');

        $other = BooksFixture::createBook(title: 'Other', slug: 'other');
        BooksFixture::createChapter($other, 'Чужая', [BooksFixture::paragraph('Чужой текст')], 1, added: '2000-01-01');

        return $book;
    }

    private function loadFb2(string $xml): DOMXPath {
        $document = new DOMDocument();
        self::assertTrue($document->loadXML($xml), 'The FB2 is well-formed XML.');
        self::assertSame('FictionBook', $document->documentElement?->localName);

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('f', self::FB2_NS);
        $xpath->registerNamespace('l', 'http://www.w3.org/1999/xlink');

        return $xpath;
    }

    private function text(DOMXPath $xpath, string $query): string {
        $texts = $this->texts($xpath, $query);
        self::assertCount(1, $texts, $query);

        return $texts[0];
    }

    /**
     * @return list<string>
     */
    private function texts(DOMXPath $xpath, string $query): array {
        $nodes = $xpath->query($query);
        self::assertNotFalse($nodes, $query);

        $texts = [];

        foreach ($nodes as $node) {
            $texts[] = trim($node->nodeValue ?? '');
        }

        return $texts;
    }
}
