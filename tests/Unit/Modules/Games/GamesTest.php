<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Games;

use BC\Core\Converter\DateConverter;
use BC\Core\DTO\MenuItemDTO;
use BC\DTO\AppSettings\NavigationDTO;
use BC\DTO\SitemapEntryDTO;
use BC\Modules\Games\Api\DataBuilder\Game\GameDataBuilder;
use BC\Modules\Games\Api\DataBuilder\GameMaterial\GameMaterialDataBuilder;
use BC\Modules\Games\Core\DTO\GameDTO;
use BC\Modules\Games\Model\Game;
use BC\Modules\Games\Model\GameMaterial;
use BC\Modules\Games\Model\GameMaterialFile;
use BC\Modules\Games\Provider\Admin\AppSettingsProvider;
use BC\Modules\Games\Provider\Admin\PageProvider;
use BC\Modules\Games\Provider\CommentsProvider;
use BC\Modules\Games\Provider\MenuItemsProvider;
use BC\Modules\Games\Provider\SitemapPagesProvider;
use BC\Provider\IPathsProvider;
use BC\Tests\Fixture\Games\GamesFixture;
use BC\Tests\Fixture\Modules\InnerProviders;
use BC\Tests\Support\SqliteSchema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Runway\Singleton\Container;

#[CoversClass(Game::class)]
#[CoversClass(GameMaterial::class)]
#[CoversClass(GameMaterialFile::class)]
#[CoversClass(GameDTO::class)]
#[CoversClass(GameDataBuilder::class)]
#[CoversClass(GameMaterialDataBuilder::class)]
#[CoversClass(SitemapPagesProvider::class)]
#[CoversClass(CommentsProvider::class)]
#[CoversClass(PageProvider::class)]
#[CoversClass(MenuItemsProvider::class)]
#[CoversClass(AppSettingsProvider::class)]
final class GamesTest extends TestCase {
    protected function setUp(): void {
        GamesFixture::createTables();
    }

    public function testMaterialUrls(): void {
        $game = new Game()->setSlug('doom');
        $file = new GameMaterial()->setGame($game)->setSlug('wad')->setType(GameMaterial::TYPE_FILE)->setUrl('/ignored');
        $article = new GameMaterial()->setGame($game)->setSlug('ignored')->setType(GameMaterial::TYPE_ARTICLE)->setUrl('/blog/doom-review');

        self::assertTrue($file->isFile());
        self::assertFalse($file->isArticle());
        self::assertSame('http://bc.test/games/doom/wad', $file->getMaterialUrl());
        self::assertFalse($article->isFile());
        self::assertTrue($article->isArticle());
        self::assertSame('http://bc.test/blog/doom-review', $article->getMaterialUrl());
        self::assertTrue(new GameMaterial()->isFile());
    }

    public function testFileUrl(): void {
        $file = new GameMaterialFile()->setPath('doom.zip');

        self::assertSame('http://bc.test/static/games/doom.zip', $file->getUrl());
        self::assertSame(PROJECT_ROOT . '/static/games/doom.zip', $file->getLocalPath());
    }

    /**
     * Removing a game removes its materials only.
     */
    public function testRemoveGameKeepsMaterialsOfOtherGames(): void {
        $doom = GamesFixture::createGame('doom');
        $quake = GamesFixture::createGame('quake');
        GamesFixture::createMaterial($doom, 'wad');
        GamesFixture::createMaterial($doom, 'review', GameMaterial::TYPE_ARTICLE);
        $kept = GamesFixture::createMaterial($quake, 'pak');

        $doom->remove();

        self::assertNull(Game::findByUniqueIdentifier($doom->getId()));
        self::assertNotNull(Game::findByUniqueIdentifier($quake->getId()));
        self::assertSame([$kept->getId()], array_map(static fn(GameMaterial $material): int => $material->getId(), GameMaterial::find()));
    }

    public function testGameDto(): void {
        $game = new Game();
        $dto = new GameDTO($game);
        $material = new GameMaterial();

        self::assertSame($game, $dto->game);
        self::assertSame([], $dto->getMaterials());
        self::assertSame($dto, $dto->addMaterial($material));
        self::assertSame([$material], $dto->getMaterials());
    }

    public function testGameDataBuilder(): void {
        $builder = new GameDataBuilder();
        // The media with the id 1: a row without a cover does not get it.
        $cover = new \BC\Model\Media()->setPath('cover.jpg')->setMime('image/jpeg')->setWidth(1)->setHeight(1)->setSize(0)
            ->setAlt('')->setParent(null)->setMd5('');
        $cover->persist();
        self::assertSame(1, $cover->getId());

        $row = $builder->buildRow(['id' => '3', 'title' => 'Doom', 'cover_id' => '0', 'count' => '2']);
        self::assertSame([3, 'Doom', null, 2], [$row->id, $row->title, $row->cover, $row->count]);
        $row = $builder->buildRow([]);
        self::assertSame([0, '', null, 0], [$row->id, $row->title, $row->cover, $row->count]);

        self::assertSame($cover->getId(), $builder->buildRow(['cover_id' => (string)$cover->getId()])->cover?->id);

        $dto = $builder->buildEntity(new Game()->setTitle('Doom')->setSlug('doom'));
        self::assertSame(['Doom', 'doom', null], [$dto->title, $dto->slug, $dto->cover]);
    }

    public function testGameMaterialRow(): void {
        $builder = new GameMaterialDataBuilder(new DateConverter('Europe/Samara'));
        $timestamp = (string)new \DateTime('2025-01-02 21:00:00', new \DateTimeZone('UTC'))->getTimestamp();

        $row = $builder->buildRow([
            'id' => '7', 'game_id' => '2', 'game_title' => 'Doom', 'type' => GameMaterial::TYPE_FILE, 'title' => 'WAD',
            'short_title' => 'Short', 'date_added' => $timestamp, 'annotation' => 'About',
        ]);
        self::assertSame(7, $row->id);
        self::assertSame(2, $row->game->id);
        self::assertSame('Doom', $row->game->title);
        self::assertSame('WAD', $row->title);
        self::assertSame('Doom', $row->game_title);
        self::assertSame('3 янв 2025', $row->date_added);
        self::assertSame(GameMaterial::TYPE_FILE, $row->type);
        self::assertSame('About', $row->annotation);

        self::assertSame('[Безымянный материал]', $builder->buildRow(['type' => GameMaterial::TYPE_FILE, 'short_title' => 'S'])->title);
        self::assertSame('[Статья] Review', $builder->buildRow(['type' => GameMaterial::TYPE_ARTICLE, 'title' => 'T', 'short_title' => 'Review'])->title);
        self::assertSame('[Безымянная статья]', $builder->buildRow(['type' => GameMaterial::TYPE_ARTICLE, 'title' => 'T'])->title);

        $empty = $builder->buildRow([]);
        self::assertSame([0, 0, '', '[Безымянная статья]', '', '-', '', ''], [
            $empty->id, $empty->game->id, $empty->game->title, $empty->title, $empty->game_title, $empty->date_added,
            $empty->type, $empty->annotation,
        ]);
    }

    public function testGameMaterialEntity(): void {
        $builder = new GameMaterialDataBuilder(new DateConverter('Europe/Samara'));
        $game = GamesFixture::createGame('doom');
        $material = GamesFixture::createMaterial($game, 'wad', GameMaterial::TYPE_ARTICLE, '/blog/x')
            ->setAnnotation('About')->setDescription(['blocks' => []])->setSetupInstructions(['blocks' => [['type' => 'x']]]);

        $dto = $builder->buildEntity($material);

        self::assertSame('Material wad', $dto->title);
        self::assertSame('Short wad', $dto->shortTitle);
        self::assertSame('wad', $dto->slug);
        self::assertSame($game->getId(), $dto->gameId);
        self::assertSame('2025-01-02T07:04:05+04:00', $dto->dateAdded);
        self::assertSame('About', $dto->annotation);
        self::assertSame(['blocks' => []], $dto->description);
        self::assertSame(['blocks' => [['type' => 'x']]], $dto->setupInstructions);
        self::assertNull($dto->file);
        self::assertSame(GameMaterial::TYPE_ARTICLE, $dto->type);
        self::assertSame('/blog/x', $dto->url);

        self::assertNull($builder->buildEntity($material->setDateAdded(null))->dateAdded);
    }

    public function testSitemap(): void {
        $doom = GamesFixture::createGame('doom');
        GamesFixture::createMaterial($doom, 'wad');
        GamesFixture::createMaterial($doom, 'review', GameMaterial::TYPE_ARTICLE, '/blog/review');

        self::assertEquals(
            [new SitemapEntryDTO('/games'), new SitemapEntryDTO('http://bc.test/games/doom/wad')],
            new SitemapPagesProvider()->getSitemapPages()
        );

        SqliteSchema::getDriver()->execute('DROP TABLE IF EXISTS `{game_materials}`');
        self::assertEquals([new SitemapEntryDTO('/games')], new SitemapPagesProvider()->getSitemapPages());
    }

    public function testComments(): void {
        $material = GamesFixture::createMaterial(GamesFixture::createGame('doom'), 'wad');
        $inner = new InnerProviders(pageExists: false);
        $provider = new CommentsProvider($inner);

        self::assertTrue($provider->isPageExist('material', $material->getId()));
        self::assertFalse($provider->isPageExist('material', 999));
        self::assertSame([], $inner->calls);
        self::assertFalse($provider->isPageExist('post', 1));
        self::assertSame([['post', 1]], $inner->calls);
        self::assertSame(['inner message'], $provider->getSuccessMessages());

        SqliteSchema::getDriver()->execute('DROP TABLE IF EXISTS `{game_materials}`');
        self::assertFalse($provider->isPageExist('material', $material->getId()));

        // A database error is "no page", not a question to the inner provider.
        $exists = new InnerProviders(pageExists: true);
        self::assertFalse(new CommentsProvider($exists)->isPageExist('material', $material->getId()));
        self::assertSame([], $exists->calls);
    }

    public function testAdminPage(): void {
        $material = GamesFixture::createMaterial(GamesFixture::createGame('doom', 'Doom'), 'wad');
        $inner = new InnerProviders();
        $provider = new PageProvider($inner);

        $page = $provider->getPage('material', $material->getId());
        self::assertSame('Material wad (Doom)', $page->title);
        self::assertSame("/admin/games/materials/{$material->getId()}", $page->url);
        self::assertSame('http://bc.test/games/doom/wad', $page->publicUrl);

        $page = $provider->getPage('material', 999);
        self::assertSame(['Неизвестный игровой материал', '', ''], [$page->title, $page->url, $page->publicUrl]);
        self::assertSame([], $inner->calls);
        self::assertSame('Inner', $provider->getPage('post', 2)->title);
        self::assertSame([['post', 2]], $inner->calls);

        SqliteSchema::getDriver()->execute('DROP TABLE IF EXISTS `{game_materials}`');
        self::assertSame('Неизвестный игровой материал', $provider->getPage('material', 1)->title);
    }

    public function testMenu(): void {
        $home = new MenuItemDTO('Главная', '/');
        $provider = new MenuItemsProvider(new InnerProviders(menuItems: [$home]));

        self::assertEquals([$home, new MenuItemDTO('Игры', 'http://bc.test/games', 10)], $provider->getMenuItems());
        $other = new MenuItemDTO('Другое', '/other');
        self::assertSame([$other, $home], $provider->sortMenuItems([$home, $other]));
    }

    public function testAdminAppSettings(): void {
        $paths = Container::getInstance()->getService(IPathsProvider::class);
        $settings = new AppSettingsProvider(new InnerProviders(), $paths)->getAppSettings();

        $icons = dirname(__DIR__, 4) . '/modules/Games/app/assets';

        self::assertCount(1, $settings->nav);
        self::assertSame(['Игры', 200, null], [$settings->nav[0]->label, $settings->nav[0]->position, $settings->nav[0]->path]);
        self::assertSame(file_get_contents("$icons/icon.svg"), $settings->nav[0]->icon);
        self::assertSame(
            [
                ['Список игр', '/admin/games', file_get_contents("$icons/games.svg"), 100],
                ['Добавить игру', '/admin/games/new', file_get_contents("$icons/games.svg"), 200],
                ['Материалы', '/admin/games/materials', file_get_contents("$icons/materials.svg"), 300],
                ['Новый материал', '/admin/games/materials/new', file_get_contents("$icons/materials.svg"), 400],
            ],
            array_map(
                static fn(NavigationDTO $item): array => [$item->label, $item->path, $item->icon, $item->position],
                $settings->nav[0]->children ?? []
            )
        );

        self::assertCount(1, $settings->modules);
        self::assertSame(['games', 'games'], [$settings->modules[0]->id, $settings->modules[0]->path]);
        self::assertSame($paths->getVersionedStaticWebPath('modules/games/remoteEntry.js'), $settings->modules[0]->remoteEntry);
    }
}
