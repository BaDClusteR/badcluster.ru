<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Music;

use BC\Core\Converter\DateConverter;
use BC\Core\DTO\MenuItemDTO;
use BC\Core\Formatter\Formatter;
use BC\DTO\AppSettings\NavigationDTO;
use BC\DTO\SitemapEntryChangeFreqEnum;
use BC\DTO\SitemapEntryDTO;
use BC\Model\Media;
use BC\Modules\Music\Api\DataBuilder\Album\AlbumDataBuilder;
use BC\Modules\Music\Api\DataBuilder\Track\TrackDataBuilder;
use BC\Modules\Music\Api\DTO\Track\SongDTO;
use BC\Modules\Music\Model\Album;
use BC\Modules\Music\Model\Song;
use BC\Modules\Music\Model\Track;
use BC\Modules\Music\Provider\Admin\AppSettingsProvider;
use BC\Modules\Music\Provider\Admin\PageProvider;
use BC\Modules\Music\Provider\CommentsProvider;
use BC\Modules\Music\Provider\MenuItemsProvider;
use BC\Modules\Music\Provider\SitemapPagesProvider;
use BC\Provider\IPathsProvider;
use BC\Tests\Fixture\Modules\InnerProviders;
use BC\Tests\Support\SqliteSchema;
use DateTime;
use DateTimeZone;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Runway\Singleton\Container;

/**
 * No files: the Song rows are never removed in the tests (removing deletes the file under static/).
 */
#[CoversClass(Album::class)]
#[CoversClass(Track::class)]
#[CoversClass(Song::class)]
#[CoversClass(SongDTO::class)]
#[CoversClass(AlbumDataBuilder::class)]
#[CoversClass(TrackDataBuilder::class)]
#[CoversClass(SitemapPagesProvider::class)]
#[CoversClass(CommentsProvider::class)]
#[CoversClass(PageProvider::class)]
#[CoversClass(MenuItemsProvider::class)]
#[CoversClass(AppSettingsProvider::class)]
final class MusicTest extends TestCase {
    protected function setUp(): void {
        SqliteSchema::createModelTables(Album::class, Track::class, Song::class, Media::class);
    }

    #[DataProvider('provideTypes')]
    public function testTypeHumanReadable(string $type, string $expected): void {
        self::assertSame($expected, new Album()->setType($type)->getTypeHumanReadable());
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideTypes(): iterable {
        yield [Album::ALBUM_TYPE_SINGLE, 'Сингл'];
        yield [Album::ALBUM_TYPE_DOUBLE_SINGLE, 'Двойной сингл'];
        yield [Album::ALBUM_TYPE_EXTENDED_PLAY, 'EP'];
        yield [Album::ALBUM_TYPE_ALBUM, 'Альбом'];
        yield ['X', ''];
    }

    public function testAlbumBasics(): void {
        $album = new Album()->setSlug('dark')->setGenre(' Rock,Metal , Industrial');

        self::assertSame('EP', $album->getTypeHumanReadable());
        self::assertSame('http://bc.test/music/dark', $album->getUrl());
        self::assertSame(['Rock', 'Metal', 'Industrial'], $album->getGenres());
        self::assertSame([''], new Album()->getGenres());
    }

    public function testTracks(): void {
        $album = $this->createAlbum('dark');
        $other = $this->createAlbum('other');
        $this->createTrack($album, 'Second', 2);
        $this->createTrack($album, 'First', 1);
        $this->createTrack($other, 'Alien', 0, explicit: true);

        self::assertSame(['First', 'Second'], array_map(static fn(Track $track): string => $track->getTitle(), $album->getTracks()));
        self::assertFalse($album->hasExplicitLanguage());
        self::assertTrue($other->hasExplicitLanguage());

        $this->createTrack($album, 'Explicit', 3, explicit: true);
        self::assertTrue($album->hasExplicitLanguage());
    }

    public function testTypeAndTracks(): void {
        $album = $this->createAlbum('dark', Album::ALBUM_TYPE_ALBUM);
        self::assertSame('Альбом • 0 треков', $album->getTypeAndTracks());

        $this->createTrack($album, 'One', 1);
        self::assertSame('Альбом • 1 трек', $album->getTypeAndTracks());
        $this->createTrack($album, 'Two', 2);
        self::assertSame('Альбом • 2 трека', $album->getTypeAndTracks());
        self::assertSame('EP • 2 трека', $album->setType(Album::ALBUM_TYPE_EXTENDED_PLAY)->getTypeAndTracks());

        // A single is a single, whatever the number of tracks.
        self::assertSame('Сингл', $album->setType(Album::ALBUM_TYPE_SINGLE)->getTypeAndTracks());
        self::assertSame('Двойной сингл', $album->setType(Album::ALBUM_TYPE_DOUBLE_SINGLE)->getTypeAndTracks());

        SqliteSchema::getDriver()->execute('DROP TABLE IF EXISTS `{tracks}`');
        self::assertSame('', $album->setType(Album::ALBUM_TYPE_ALBUM)->getTypeAndTracks());
    }

    #[DataProvider('provideTrackCounts')]
    public function testTracksText(int $count, string $expected): void {
        self::assertSame($expected, new AlbumDataBuilder(new DateConverter('UTC'), new Formatter())->getTracksText($count));
    }

    /**
     * @return iterable<array{int, string}>
     */
    public static function provideTrackCounts(): iterable {
        yield [0, '0 песен'];
        yield [1, '1 песня'];
        yield [2, '2 песни'];
        yield [4, '4 песни'];
        yield [5, '5 песен'];
        yield [9, '9 песен'];
        yield [10, '10 песен'];
        yield [11, '11 песен'];
        yield [14, '14 песен'];
        yield [20, '20 песен'];
        yield [21, '21 песня'];
        yield [22, '22 песни'];
        yield [25, '25 песен'];
        yield [101, '101 песня'];
        yield [111, '111 песен'];
        yield [112, '112 песен'];
        yield [120, '120 песен'];
        yield [123, '123 песни'];
        yield [1011, '1011 песен'];
        yield [1021, '1021 песня'];
    }

    public function testAlbumRowAndEntity(): void {
        $builder = new AlbumDataBuilder(new DateConverter('Europe/Samara'), new Formatter());
        $album = $this->createAlbum('dark', Album::ALBUM_TYPE_ALBUM);
        $album->setAnnotation('About')->setShortAnnotation('Short')->setMusicBy('Me')->setVisualBy('Him')->setCoverBy('Her')
            ->setPosition(4)->persist();
        $this->createTrack($album, 'One', 1);
        $this->createTrack($album, 'Two', 2);
        $this->createTrack($this->createAlbum('other'), 'Alien', 1);

        $row = $builder->buildRow($album);
        self::assertSame($album->getId(), $row->id);
        self::assertNull($row->cover);
        self::assertSame('Album dark', $row->title);
        self::assertSame('Rock, Metal', $row->genre);
        self::assertSame('Альбом', $row->type);
        self::assertSame('2 янв 2025', $row->releaseDate);
        self::assertSame('2 песни', $row->tracks);

        $dto = $builder->buildEntity($album);
        self::assertSame('Album dark', $dto->title);
        self::assertNull($dto->cover);
        self::assertSame('dark', $dto->slug);
        self::assertSame('Rock, Metal', $dto->genre);
        self::assertSame(Album::ALBUM_TYPE_ALBUM, $dto->type);
        self::assertSame('2025-01-02T01:00:00+04:00', $dto->releaseDate);
        self::assertSame('About', $dto->annotation);
        self::assertSame('Short', $dto->shortAnnotation);
        self::assertSame(['Me', 'Him', 'Her'], [$dto->musicBy, $dto->visualBy, $dto->coverBy]);
        self::assertSame(4, $dto->position);
    }

    public function testTrackBuilder(): void {
        $builder = new TrackDataBuilder(new Formatter());
        $album = $this->createAlbum('dark');
        $track = $this->createTrack($album, 'One', 3, explicit: true, duration: 185);
        $track->setSourceUrl('https://source')->setLyrics('La la')->setClipUrl('https://clip')->setAnnotation('About');

        $row = $builder->buildRow($track);
        self::assertSame([$track->getId(), 'One', '3:05'], [$row->id, $row->title, $row->duration]);

        $dto = $builder->buildEntity($track);
        self::assertSame('One', $dto->title);
        self::assertTrue($dto->explicitLanguage);
        self::assertSame('https://source', $dto->sourceUrl);
        self::assertSame('La la', $dto->lyrics);
        self::assertSame('https://clip', $dto->clipUrl);
        self::assertSame(3, $dto->position);
        self::assertSame('About', $dto->annotation);
        self::assertSame($album->getId(), $dto->albumId);
        self::assertSame($track->getSong()?->toFileDTO()->id, $dto->song->id);
        self::assertSame('one.mp3', $dto->song->filename);
        self::assertSame(2048, $dto->song->size);
        self::assertSame('2 Кб', $dto->song->sizeHumanReadable);
        self::assertSame('audio/mpeg', $dto->song->mime);
        self::assertSame('http://bc.test/static/music/one.mp3', $dto->song->url);
        self::assertSame('3:05', $dto->song->duration);
        self::assertSame('http://bc.test/static/music/one.mp3', $track->getUrl());
    }

    public function testSitemap(): void {
        $this->createAlbum('dark');

        self::assertEquals(
            [
                new SitemapEntryDTO('/music'),
                new SitemapEntryDTO('http://bc.test/music/dark', SitemapEntryChangeFreqEnum::MONTHLY),
            ],
            new SitemapPagesProvider()->getSitemapPages()
        );

        SqliteSchema::getDriver()->execute('DROP TABLE IF EXISTS `{albums}`');
        self::assertEquals([new SitemapEntryDTO('/music')], new SitemapPagesProvider()->getSitemapPages());
    }

    public function testComments(): void {
        $album = $this->createAlbum('dark');
        $inner = new InnerProviders(pageExists: false);
        $provider = new CommentsProvider($inner);

        self::assertTrue($provider->isPageExist('album', $album->getId()));
        self::assertFalse($provider->isPageExist('album', 999));
        self::assertSame([], $inner->calls);
        self::assertFalse($provider->isPageExist('post', 1));
        self::assertSame([['post', 1]], $inner->calls);
        self::assertSame(['inner message'], $provider->getSuccessMessages());

        SqliteSchema::getDriver()->execute('DROP TABLE IF EXISTS `{albums}`');
        self::assertFalse($provider->isPageExist('album', $album->getId()));

        // A database error is "no page", not a question to the inner provider.
        $exists = new InnerProviders(pageExists: true);
        self::assertFalse(new CommentsProvider($exists)->isPageExist('album', $album->getId()));
        self::assertSame([], $exists->calls);
    }

    public function testAdminPage(): void {
        $album = $this->createAlbum('dark');
        $inner = new InnerProviders();
        $provider = new PageProvider($inner);

        $page = $provider->getPage('album', $album->getId());
        self::assertSame('Музыкальный сборник "Album dark"', $page->title);
        self::assertSame("/admin/music/{$album->getId()}", $page->url);
        self::assertSame('http://bc.test/music/dark', $page->publicUrl);

        $page = $provider->getPage('album', 999);
        self::assertSame(['Неизвестный музыкальный сборник', '', ''], [$page->title, $page->url, $page->publicUrl]);
        self::assertSame([], $inner->calls);
        self::assertSame('Inner', $provider->getPage('post', 2)->title);
        self::assertSame([['post', 2]], $inner->calls);

        SqliteSchema::getDriver()->execute('DROP TABLE IF EXISTS `{albums}`');
        self::assertSame('Неизвестный музыкальный сборник', $provider->getPage('album', 1)->title);
    }

    public function testMenu(): void {
        $home = new MenuItemDTO('Главная', '/');
        $provider = new MenuItemsProvider(new InnerProviders(menuItems: [$home]));

        self::assertEquals([$home, new MenuItemDTO('Музыка', 'http://bc.test/music', 400)], $provider->getMenuItems());
        $other = new MenuItemDTO('Другое', '/other');
        self::assertSame([$other, $home], $provider->sortMenuItems([$home, $other]));
    }

    public function testAdminAppSettings(): void {
        $paths = Container::getInstance()->getService(IPathsProvider::class);
        $settings = new AppSettingsProvider(new InnerProviders(), $paths)->getAppSettings();
        $icons = dirname(__DIR__, 4) . '/modules/Music/app/assets';

        self::assertCount(1, $settings->nav);
        self::assertSame(['Музыка', 700, null], [$settings->nav[0]->label, $settings->nav[0]->position, $settings->nav[0]->path]);
        self::assertSame(file_get_contents("$icons/icon.svg"), $settings->nav[0]->icon);
        self::assertSame(
            [
                ['Альбомы', '/admin/music', file_get_contents("$icons/albums.svg"), 100],
                ['Добавить альбом', '/admin/music/new', file_get_contents("$icons/add-album.svg"), 200],
            ],
            array_map(
                static fn(NavigationDTO $item): array => [$item->label, $item->path, $item->icon, $item->position],
                $settings->nav[0]->children ?? []
            )
        );
        self::assertCount(1, $settings->modules);
        self::assertSame(['music', 'music'], [$settings->modules[0]->id, $settings->modules[0]->path]);
        self::assertSame($paths->getVersionedStaticWebPath('modules/music/remoteEntry.js'), $settings->modules[0]->remoteEntry);
    }

    private function createAlbum(string $slug, string $type = Album::ALBUM_TYPE_EXTENDED_PLAY): Album {
        $album = new Album()
            ->setTitle("Album $slug")
            ->setSlug($slug)
            ->setType($type)
            ->setGenre('Rock, Metal')
            ->setAnnotation('')
            ->setReleaseDate(new DateTime('2025-01-01 21:00:00', new DateTimeZone('UTC')));
        $album->persist();

        return $album;
    }

    private function createTrack(Album $album, string $title, int $position, bool $explicit = false, int $duration = 60): Track {
        $song = new Song()->setPath(strtolower($title) . '.mp3')->setMime('audio/mpeg')->setSize(2048)->setHash('')->setDuration($duration);
        $song->persist();

        // A new track has no song: setSong() has no old song (and file) to remove.
        $track = new Track()->setAlbum($album)->setTitle($title)->setPosition($position)->setExplicitLanguage($explicit)
            ->setSong($song);
        $track->persist();

        return $track;
    }
}
