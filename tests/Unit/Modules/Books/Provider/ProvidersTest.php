<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Books\Provider;

use BC\Core\DTO\MenuItemDTO;
use BC\DTO\AppSettings\NavigationDTO;
use BC\DTO\SitemapEntryChangeFreqEnum;
use BC\DTO\SitemapEntryDTO;
use BC\Modules\Books\Provider\Admin\AppSettingsProvider;
use BC\Modules\Books\Provider\Admin\PageProvider;
use BC\Modules\Books\Provider\CommentsProvider;
use BC\Modules\Books\Provider\MenuItemsProvider;
use BC\Modules\Books\Provider\PreloadFontProvider;
use BC\Modules\Books\Provider\SitemapPagesProvider;
use BC\Modules\Books\Widget\Page\ChapterPage;
use BC\Provider\IPathsProvider;
use BC\Tests\Fixture\Books\BooksFixture;
use BC\Tests\Fixture\Modules\InnerProviders;
use BC\Tests\Support\SqliteSchema;
use BC\Widget\Page\Page404;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Runway\Singleton\Container;

#[CoversClass(SitemapPagesProvider::class)]
#[CoversClass(CommentsProvider::class)]
#[CoversClass(PageProvider::class)]
#[CoversClass(MenuItemsProvider::class)]
#[CoversClass(PreloadFontProvider::class)]
#[CoversClass(AppSettingsProvider::class)]
final class ProvidersTest extends TestCase {
    protected function setUp(): void {
        BooksFixture::createTables();
    }

    public function testSitemap(): void {
        $book = BooksFixture::createBook();
        BooksFixture::createChapter($book, 'Вторая', [], 2);
        BooksFixture::createChapter($book, 'Первая', [], 1);
        BooksFixture::createChapter($book, 'Черновик', [], 3, published: false);
        BooksFixture::createBook(slug: 'empty');

        self::assertEquals(
            [
                new SitemapEntryDTO('/books'),
                new SitemapEntryDTO('http://bc.test/books/doom'),
                new SitemapEntryDTO('http://bc.test/books/doom/chapter-1'),
                new SitemapEntryDTO('http://bc.test/books/doom/chapter-2'),
                new SitemapEntryDTO('http://bc.test/books/empty'),
            ],
            new SitemapPagesProvider()->getSitemapPages()
        );
        self::assertSame(SitemapEntryChangeFreqEnum::DAILY, new SitemapPagesProvider()->getSitemapPages()[1]->changeFreq);
    }

    public function testSitemapWithoutTables(): void {
        SqliteSchema::getDriver()->execute('DROP TABLE IF EXISTS `{books}`');

        self::assertEquals([new SitemapEntryDTO('/books')], new SitemapPagesProvider()->getSitemapPages());
    }

    public function testCommentsOfBook(): void {
        $book = BooksFixture::createBook();
        $inner = new InnerProviders(pageExists: false);
        $provider = new CommentsProvider($inner);

        self::assertTrue($provider->isPageExist('book', $book->getId()));
        self::assertFalse($provider->isPageExist('book', $book->getId() + 1));
        self::assertSame([], $inner->calls);

        self::assertFalse($provider->isPageExist('post', $book->getId()));
        self::assertSame([['post', $book->getId()]], $inner->calls);
        self::assertSame(['inner message'], $provider->getSuccessMessages());
    }

    public function testAdminPageOfBook(): void {
        $book = BooksFixture::createBook();
        $inner = new InnerProviders();
        $provider = new PageProvider($inner);

        $page = $provider->getPage('book', $book->getId());
        self::assertSame('Doom', $page->title);
        self::assertSame("/admin/books/{$book->getId()}", $page->url);
        self::assertSame('http://bc.test/books/doom', $page->publicUrl);

        $page = $provider->getPage('book', 999);
        self::assertSame('Неизвестное произведение', $page->title);
        self::assertSame('', $page->url);
        self::assertSame('', $page->publicUrl);
        self::assertSame([], $inner->calls);

        self::assertSame('Inner', $provider->getPage('album', 5)->title);
        self::assertSame([['album', 5]], $inner->calls);
    }

    public function testMenu(): void {
        $first = new MenuItemDTO('Главная', '/', 0);
        $provider = new MenuItemsProvider(new InnerProviders(menuItems: [$first]));

        self::assertEquals([$first, new MenuItemDTO('Книги', 'http://bc.test/books', 20)], $provider->getMenuItems());
        self::assertSame([$first], $provider->sortMenuItems([$first]));
        $second = new MenuItemDTO('Другое', '/other');
        self::assertSame([$second, $first], $provider->sortMenuItems([$first, $second]));
    }

    public function testFontsOfChapterPage(): void {
        $book = BooksFixture::createBook();
        $chapter = BooksFixture::createChapter($book, 'Глава', [], 1);
        $provider = new PreloadFontProvider(new InnerProviders());

        self::assertSame(
            ['inner.woff2', 'ibm-plex-serif/cyrillic.woff2', 'ibm-plex-serif/latin.woff2'],
            $provider->getPreloadFontPaths(new ChapterPage(['chapter' => $chapter]))
        );
        self::assertSame(['inner.woff2'], $provider->getPreloadFontPaths(new Page404()));
    }

    public function testAdminAppSettings(): void {
        $paths = Container::getInstance()->getService(IPathsProvider::class);
        $settings = new AppSettingsProvider(new InnerProviders(), $paths)->getAppSettings();
        $icons = dirname(__DIR__, 5) . '/modules/Books/app/assets';

        self::assertCount(1, $settings->nav);
        $nav = $settings->nav[0];
        self::assertSame('Книги', $nav->label);
        self::assertSame(200, $nav->position);
        self::assertNull($nav->path);
        self::assertSame(file_get_contents("$icons/books.svg"), $nav->icon);
        self::assertSame(
            [
                ['Список книг', '/admin/books', file_get_contents("$icons/books.svg"), 100],
                ['Добавить произведение', '/admin/books/new', file_get_contents("$icons/add-book.svg"), 100],
            ],
            array_map(
                static fn(NavigationDTO $item): array => [$item->label, $item->path, $item->icon, $item->position],
                $nav->children ?? []
            )
        );

        self::assertCount(1, $settings->modules);
        self::assertSame('books', $settings->modules[0]->id);
        self::assertSame('books', $settings->modules[0]->path);
        self::assertSame($paths->getVersionedStaticWebPath('modules/books/remoteEntry.js'), $settings->modules[0]->remoteEntry);
        self::assertStringStartsWith('http://bc.test/static/modules/books/remoteEntry.js', $settings->modules[0]->remoteEntry);
    }
}
