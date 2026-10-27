<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Blog\Provider;

use BC\Core\DTO\MenuItemDTO;
use BC\DTO\AppSettings\NavigationDTO;
use BC\DTO\PulseItemDTO;
use BC\DTO\SitemapEntryDTO;
use BC\Modules\Blog\Provider\Admin\AppSettingsProvider;
use BC\Modules\Blog\Provider\Admin\PageProvider;
use BC\Modules\Blog\Provider\CommentsProvider;
use BC\Modules\Blog\Provider\MenuItemsProvider;
use BC\Modules\Blog\Provider\NotesProvider;
use BC\Modules\Blog\Provider\PulseItemsProvider;
use BC\Modules\Blog\Provider\SitemapPagesProvider;
use BC\Provider\IPathsProvider;
use BC\Tests\Fixture\Blog\BlogFixture;
use BC\Tests\Fixture\Modules\InnerProviders;
use BC\Tests\Support\LoggerSpy;
use BC\Tests\Support\SqliteSchema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Runway\Singleton\Container;

#[CoversClass(MenuItemsProvider::class)]
#[CoversClass(SitemapPagesProvider::class)]
#[CoversClass(PulseItemsProvider::class)]
#[CoversClass(CommentsProvider::class)]
#[CoversClass(PageProvider::class)]
#[CoversClass(AppSettingsProvider::class)]
final class ProvidersTest extends TestCase {
    protected function setUp(): void {
        BlogFixture::createTables();
    }

    public function testMenu(): void {
        $home = new MenuItemDTO('Главная', '/');
        $guest = new InnerProviders(menuItems: [$home]);
        $provider = new MenuItemsProvider($guest, new NotesProvider(new LoggerSpy(), $guest));
        $blog = new MenuItemDTO('Блог', 'http://bc.test/blog', 0);
        $notes = new MenuItemDTO('Заметки', 'http://bc.test/notes', 5);

        self::assertEquals([$home, $blog], $provider->getMenuItems());

        BlogFixture::createNote('note');
        self::assertEquals([$home, $blog, $notes], $provider->getMenuItems());

        $other = new MenuItemDTO('Другое', '/other');
        self::assertSame([$other, $home], $provider->sortMenuItems([$home, $other]));
    }

    public function testSitemap(): void {
        $guest = new InnerProviders();
        $provider = new SitemapPagesProvider(new NotesProvider(new LoggerSpy(), $guest));

        self::assertEquals([new SitemapEntryDTO('/blog')], $provider->getSitemapPages());

        BlogFixture::createTag('doom');
        BlogFixture::createPost('post');
        BlogFixture::createPost('draft', published: false);
        BlogFixture::createNote('note');
        BlogFixture::createNote('draft-note', published: false);

        self::assertEquals(
            [
                new SitemapEntryDTO('/blog'),
                new SitemapEntryDTO('/notes'),
                new SitemapEntryDTO('http://bc.test/blog/tag/doom'),
                new SitemapEntryDTO('http://bc.test/blog/post'),
                new SitemapEntryDTO('http://bc.test/notes/note'),
            ],
            $provider->getSitemapPages()
        );
    }

    public function testSitemapWithDatabaseError(): void {
        BlogFixture::createPost('post');
        SqliteSchema::getDriver()->execute('DROP TABLE IF EXISTS `{tags}`');

        self::assertEquals(
            [new SitemapEntryDTO('/blog')],
            new SitemapPagesProvider(new NotesProvider(new LoggerSpy(), new InnerProviders()))->getSitemapPages()
        );
    }

    public function testPulseItemOfLatestPost(): void {
        $provider = new PulseItemsProvider();
        self::assertSame([], self::getPulseItems($provider));

        $cover = BlogFixture::createMedia('cover.jpg');
        BlogFixture::createPost('old', publishDate: '2025-01-01', cover: $cover, annotation: 'Old');
        BlogFixture::createPost('draft', published: false, publishDate: '2025-03-01', cover: $cover, annotation: 'Draft');
        BlogFixture::createPost('new', publishDate: '2025-02-01', title: 'New post', cover: $cover, annotation: 'About');

        $items = self::getPulseItems($provider);

        self::assertCount(1, $items);
        self::assertSame('New post', $items[0]->title);
        self::assertSame('http://bc.test/blog/new', $items[0]->url);
        self::assertSame('Блог', $items[0]->tag);
        self::assertSame('About', $items[0]->text);
        self::assertSame($cover->getId(), $items[0]->image?->getId());
        self::assertSame(500, $items[0]->position);
    }

    public function testNoPulseItemWithoutAnnotationOrCover(): void {
        BlogFixture::createPost('no-cover', annotation: 'About');
        self::assertSame([], self::getPulseItems(new PulseItemsProvider()));

        BlogFixture::createTables();
        BlogFixture::createPost('no-annotation', cover: BlogFixture::createMedia('cover.jpg'));
        self::assertSame([], self::getPulseItems(new PulseItemsProvider()));
    }

    /**
     * @return list<PulseItemDTO>
     *
     * @phpstan-impure The items come from the database.
     */
    private static function getPulseItems(PulseItemsProvider $provider): array {
        return self::toPulseItems($provider->getPulseItems());
    }

    /**
     * @param array<mixed> $items
     *
     * @return list<PulseItemDTO>
     */
    private static function toPulseItems(array $items): array {
        return array_values(array_map(
            static function (mixed $item): PulseItemDTO {
                self::assertInstanceOf(PulseItemDTO::class, $item);

                return $item;
            },
            $items
        ));
    }

    public function testCommentsOfPost(): void {
        $published = BlogFixture::createPost('post');
        $draft = BlogFixture::createPost('draft', published: false);
        $guest = new InnerProviders(isAuthenticated: false, pageExists: false);
        $admin = new InnerProviders(isAuthenticated: true, pageExists: false);

        self::assertTrue(new CommentsProvider($guest, $guest)->isPageExist('post', $published->getId()));
        self::assertFalse(new CommentsProvider($guest, $guest)->isPageExist('post', $draft->getId()));
        self::assertTrue(new CommentsProvider($admin, $admin)->isPageExist('post', $draft->getId()));
        self::assertFalse(new CommentsProvider($admin, $admin)->isPageExist('post', 999));
        self::assertSame([], $guest->calls);

        self::assertFalse(new CommentsProvider($guest, $guest)->isPageExist('book', $published->getId()));
        self::assertSame([['book', $published->getId()]], $guest->calls);
        self::assertSame(['inner message'], new CommentsProvider($guest, $guest)->getSuccessMessages());

        SqliteSchema::getDriver()->execute('DROP TABLE IF EXISTS `{posts}`');
        // A database error is "no page", not a question to the inner provider.
        $exists = new InnerProviders(isAuthenticated: true, pageExists: true);
        self::assertFalse(new CommentsProvider($exists, $exists)->isPageExist('post', 1));
        self::assertSame([], $exists->calls);
    }

    public function testAdminPageOfPost(): void {
        $post = BlogFixture::createPost('post', title: 'Title');
        $inner = new InnerProviders();
        $provider = new PageProvider($inner);

        $page = $provider->getPage('post', $post->getId());
        self::assertSame('Пост "Title"', $page->title);
        self::assertSame("/admin/blog/{$post->getId()}", $page->url);
        self::assertSame('http://bc.test/blog/post', $page->publicUrl);

        $page = $provider->getPage('post', 999);
        self::assertSame(['Неизвестный пост', '', ''], [$page->title, $page->url, $page->publicUrl]);
        self::assertSame([], $inner->calls);

        self::assertSame('Inner', $provider->getPage('book', 1)->title);
        self::assertSame([['book', 1]], $inner->calls);

        SqliteSchema::getDriver()->execute('DROP TABLE IF EXISTS `{posts}`');
        self::assertSame('Неизвестный пост', $provider->getPage('post', 1)->title);
    }

    public function testAdminAppSettings(): void {
        $paths = Container::getInstance()->getService(IPathsProvider::class);
        $settings = new AppSettingsProvider(new InnerProviders(), $paths)->getAppSettings();
        $icons = dirname(__DIR__, 5) . '/modules/Blog/app/assets';

        self::assertCount(1, $settings->nav);
        self::assertSame('Блог', $settings->nav[0]->label);
        self::assertSame(100, $settings->nav[0]->position);
        self::assertSame(file_get_contents("$icons/icon.svg"), $settings->nav[0]->icon);
        self::assertSame(
            [
                ['Посты', '/admin/blog', file_get_contents("$icons/posts.svg"), 100],
                ['Новый пост', '/admin/blog/new', file_get_contents("$icons/post.svg"), 200],
                ['Тэги', '/admin/blog/tags', file_get_contents("$icons/tags.svg"), 300],
                ['Заметки', '/admin/blog/notes', file_get_contents("$icons/notes.svg"), 400],
                ['Добавить заметку', '/admin/blog/notes/new', file_get_contents("$icons/note.svg"), 500],
            ],
            array_map(
                static fn(NavigationDTO $item): array => [$item->label, $item->path, $item->icon, $item->position],
                $settings->nav[0]->children ?? []
            )
        );
        self::assertCount(1, $settings->modules);
        self::assertSame(['blog', 'blog'], [$settings->modules[0]->id, $settings->modules[0]->path]);
        self::assertSame($paths->getVersionedStaticWebPath('modules/blog/remoteEntry.js'), $settings->modules[0]->remoteEntry);
    }
}
