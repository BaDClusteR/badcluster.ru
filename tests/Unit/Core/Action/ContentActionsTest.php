<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Core\Action;

use BC\Core\Action\DTO\CreatePhotoTagRequest;
use BC\Core\Action\DTO\CreatePulseItemRequest;
use BC\Core\Action\DTO\CreateStaticPageRequest;
use BC\Core\Action\DTO\SaveMediaRequest;
use BC\Core\Action\DTO\SavePhotoTagRequest;
use BC\Core\Action\DTO\SavePulseItemRequest;
use BC\Core\Action\DTO\SaveStaticPageRequest;
use BC\Core\Action\Media\SaveMediaAction;
use BC\Core\Action\PhotoTag\APhotoTagAction;
use BC\Core\Action\PhotoTag\CreatePhotoTagAction;
use BC\Core\Action\PhotoTag\SavePhotoTagAction;
use BC\Core\Action\PulseItem\APulseItemAction;
use BC\Core\Action\PulseItem\CreatePulseItemAction;
use BC\Core\Action\PulseItem\SavePulseItemAction;
use BC\Core\Action\StaticPage\AStaticPageAction;
use BC\Core\Action\StaticPage\CreateStaticPageAction;
use BC\Core\Action\StaticPage\SaveStaticPageAction;
use BC\Model\Media;
use BC\Model\PhotoTag;
use BC\Model\PulseItem;
use BC\Model\StaticPage;
use BC\Tests\Support\SqliteSchema;
use BC\Tests\Unit\Core\Converter\MediaConverterTest;
use DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Runway\Exception\Exception;

#[CoversClass(AStaticPageAction::class)]
#[CoversClass(CreateStaticPageAction::class)]
#[CoversClass(SaveStaticPageAction::class)]
#[CoversClass(APhotoTagAction::class)]
#[CoversClass(CreatePhotoTagAction::class)]
#[CoversClass(SavePhotoTagAction::class)]
#[CoversClass(APulseItemAction::class)]
#[CoversClass(CreatePulseItemAction::class)]
#[CoversClass(SavePulseItemAction::class)]
#[CoversClass(SaveMediaAction::class)]
final class ContentActionsTest extends TestCase {
    protected function setUp(): void {
        SqliteSchema::createModelTables(StaticPage::class, PhotoTag::class, PulseItem::class, Media::class);
    }

    public function testCreateStaticPage(): void {
        $media = MediaConverterTest::createMedia('a.jpg', 10, 10, 'image/jpeg', '');
        $publishDate = new DateTime('2024-05-06 07:08:09');

        $page = new CreateStaticPageAction()->run(self::createPageRequest(
            title: ' О сайте ',
            slug: ' about_Me-2 ',
            content: ['blocks' => [['type' => 'media', 'data' => ['media' => ['id' => $media->getId(), 'url' => 'x', 'alt' => 'Alt', 'width' => 5, 'height' => 6]]]]],
            publishDate: $publishDate
        ))->staticPage;

        $stored = StaticPage::findByUniqueIdentifier($page->getId());
        self::assertNotNull($stored);
        self::assertSame('О сайте', $stored->getTitle());
        self::assertSame('Коротко', $stored->getShortTitle());
        self::assertSame('about_Me-2', $stored->getSlug());
        self::assertSame(['blocks' => [['type' => 'media', 'data' => ['media' => ['id' => $media->getId()]]]]], $stored->getContent());
        self::assertTrue($stored->getPublished());
        self::assertFalse($stored->getIndexable());
        self::assertTrue($stored->getTextBlock());
        self::assertSame($publishDate->getTimestamp(), $stored->getPublishDate()?->getTimestamp());
        self::assertSame('Описание', $stored->getMetaDescription());
        self::assertSame('Назад', $stored->getBackLinkText());
        self::assertSame('/back', $stored->getBackLinkUrl());
        self::assertSame('Alt', Media::findByUniqueIdentifier($media->getId())?->getAlt(), 'The media data is saved.');
    }

    /**
     * @return iterable<string, array{string, string, array<string, string>}>
     */
    public static function provideInvalidPages(): iterable {
        yield 'no title' => [' ', 'slug', ['title' => 'Укажите заголовок страницы']];
        yield 'no slug' => ['Title', ' ', ['slug' => 'Укажите слаг страницы']];
        yield 'slug with a slash' => ['Title', 'a/b', ['slug' => 'Слаг может содержать только латиницу, цифры, дефис и подчёркивание']];
        yield 'cyrillic slug' => ['Title', 'страница', ['slug' => 'Слаг может содержать только латиницу, цифры, дефис и подчёркивание']];
        yield 'slug with a dot' => ['Title', 'page.html', ['slug' => 'Слаг может содержать только латиницу, цифры, дефис и подчёркивание']];
        yield 'nothing' => ['', '', ['title' => 'Укажите заголовок страницы', 'slug' => 'Укажите слаг страницы']];
    }

    /**
     * @param array<string, string> $errors
     */
    #[DataProvider('provideInvalidPages')]
    public function testInvalidStaticPage(string $title, string $slug, array $errors): void {
        self::assertSame(
            $errors,
            FactAndRedirectActionsTest::getErrors(
                static fn() => new CreateStaticPageAction()->run(self::createPageRequest(title: $title, slug: $slug))
            )
        );
        self::assertSame([], StaticPage::find());
    }

    public function testStaticPageSlugIsUnique(): void {
        $page = new CreateStaticPageAction()->run(self::createPageRequest(title: 'About', slug: 'About'))->staticPage;

        self::assertSame(
            ['slug' => "Такой слаг уже занят страницей #{$page->getId()} \"About\""],
            FactAndRedirectActionsTest::getErrors(
                static fn() => new CreateStaticPageAction()->run(self::createPageRequest(title: 'Other', slug: 'about'))
            )
        );
    }

    public function testSaveStaticPage(): void {
        $page = new CreateStaticPageAction()->run(self::createPageRequest(title: 'About', slug: 'about'))->staticPage;
        $other = new CreateStaticPageAction()->run(self::createPageRequest(title: 'Other', slug: 'other'))->staticPage;

        new SaveStaticPageAction()->run(new SaveStaticPageRequest(
            id: $page->getId(),
            title: 'New title',
            shortTitle: '',
            content: [],
            slug: 'ABOUT',
            published: false,
            indexable: true,
            textBlock: false,
            publishDate: null,
            metaDescription: '',
            backLinkText: '',
            backLinkUrl: ''
        ));

        $stored = StaticPage::findByUniqueIdentifier($page->getId());
        self::assertNotNull($stored);
        self::assertSame(['New title', 'ABOUT', false, true, false], [
            $stored->getTitle(),
            $stored->getSlug(),
            $stored->getPublished(),
            $stored->getIndexable(),
            $stored->getTextBlock(),
        ]);
        self::assertNull($stored->getPublishDate());

        self::assertSame(
            ['slug' => "Такой слаг уже занят страницей #{$other->getId()} \"Other\""],
            FactAndRedirectActionsTest::getErrors(static fn() => new SaveStaticPageAction()->run(new SaveStaticPageRequest(
                id: $page->getId(),
                title: 'New title',
                shortTitle: '',
                content: [],
                slug: 'other',
                published: false,
                indexable: true,
                textBlock: false,
                publishDate: null,
                metaDescription: '',
                backLinkText: '',
                backLinkUrl: ''
            )))
        );
    }

    public function testSaveUnknownStaticPage(): void {
        $this->expectException(Exception::class);

        new SaveStaticPageAction()->run(new SaveStaticPageRequest(404, 'T', '', [], 'slug', false, false, false, null, '', '', ''));
    }

    /**
     * Latin titles: LOWER() of SQLite does not change the other letters (MySQL compares them case-insensitively anyway).
     */
    public function testPhotoTags(): void {
        $first = new CreatePhotoTagAction()->run(new CreatePhotoTagRequest(' Nature '))->tag;
        $second = new CreatePhotoTagAction()->run(new CreatePhotoTagRequest('City', 250))->tag;
        $third = new CreatePhotoTagAction()->run(new CreatePhotoTagRequest('Люди'))->tag;

        self::assertSame(['Nature', 100], self::getTag($first->getId()));
        self::assertSame(['City', 250], self::getTag($second->getId()));
        self::assertSame(['Люди', 350], self::getTag($third->getId()), 'The next position is the biggest one + 100.');

        self::assertSame(
            ['title' => "Такой тэг уже есть (#{$first->getId()})"],
            FactAndRedirectActionsTest::getErrors(static fn() => new CreatePhotoTagAction()->run(new CreatePhotoTagRequest(' NATURE ')))
        );
        self::assertSame(
            ['title' => 'Укажите название тэга'],
            FactAndRedirectActionsTest::getErrors(static fn() => new CreatePhotoTagAction()->run(new CreatePhotoTagRequest('  ')))
        );

        new SavePhotoTagAction()->run(new SavePhotoTagRequest($first->getId(), 'nature', 5));
        self::assertSame(['nature', 5], self::getTag($first->getId()), 'The own title is not a conflict.');

        self::assertSame(
            ['title' => "Такой тэг уже есть (#{$second->getId()})"],
            FactAndRedirectActionsTest::getErrors(static fn() => new SavePhotoTagAction()->run(new SavePhotoTagRequest($first->getId(), 'city')))
        );
    }

    public function testSaveUnknownPhotoTag(): void {
        $this->expectException(Exception::class);

        new SavePhotoTagAction()->run(new SavePhotoTagRequest(404, 'Tag'));
    }

    public function testPulseItems(): void {
        $image = MediaConverterTest::createMedia('p.png', 10, 10, 'image/png', '');

        $item = new CreatePulseItemAction()->run(new CreatePulseItemRequest(
            image: $image,
            tag: ' tag ',
            title: ' Title ',
            text: ' Text ',
            statusTitle: ' Status ',
            statusText: ' Status text ',
            icon: ' icon ',
            position: 7,
            url: ' https://example.com ',
            isTall: true,
            isSurfaced: true
        ))->item;

        $stored = PulseItem::findByUniqueIdentifier($item->getId());
        self::assertNotNull($stored);
        self::assertSame(
            ['tag', 'Title', 'Text', 'Status', 'Status text', 'icon', 7, 'https://example.com', true, true, $image->getId()],
            [
                $stored->getTag(),
                $stored->getTitle(),
                $stored->getText(),
                $stored->getStatusTitle(),
                $stored->getStatusText(),
                $stored->getIcon(),
                $stored->getPosition(),
                $stored->getUrl(),
                $stored->getIsTall(),
                $stored->getIsSurfaced(),
                $stored->getImage()?->getId(),
            ]
        );

        new SavePulseItemAction()->run(new SavePulseItemRequest(id: $item->getId(), image: null, title: 'New', url: '  '));

        $stored = PulseItem::findByUniqueIdentifier($item->getId());
        self::assertNotNull($stored);
        self::assertSame(['New', null, null, false, false, ''], [
            $stored->getTitle(),
            $stored->getUrl(),
            $stored->getImage(),
            $stored->getIsTall(),
            $stored->getIsSurfaced(),
            $stored->getTag(),
        ]);
    }

    public function testInvalidPulseItem(): void {
        $video = MediaConverterTest::createMedia('v.mp4', 10, 10, 'video/mp4', '');

        self::assertSame(
            ['title' => 'Укажите заголовок', 'image' => 'Файл не является изображением'],
            FactAndRedirectActionsTest::getErrors(static fn() => new CreatePulseItemAction()->run(new CreatePulseItemRequest(image: $video, title: ' ')))
        );
        self::assertSame([], PulseItem::find());
    }

    public function testSaveUnknownPulseItem(): void {
        $this->expectException(Exception::class);

        new SavePulseItemAction()->run(new SavePulseItemRequest(id: 404, image: null, title: 'Title'));
    }

    public function testSaveMedia(): void {
        $media = MediaConverterTest::createMedia('a.jpg', 10, 20, 'image/jpeg', 'Old');

        $saved = new SaveMediaAction()->run(new SaveMediaRequest($media->getId(), 0, 30, 'New'))->media;

        self::assertSame($media->getId(), $saved->getId());
        $stored = Media::findByUniqueIdentifier($media->getId());
        self::assertNotNull($stored);
        self::assertSame([0, 30, 'New'], [$stored->getWidth(), $stored->getHeight(), $stored->getAlt()]);

        self::assertSame(
            ['width' => 'Ширина не может быть отрицательной', 'height' => 'Высота не может быть отрицательной'],
            FactAndRedirectActionsTest::getErrors(static fn() => new SaveMediaAction()->run(new SaveMediaRequest($media->getId(), -1, -1)))
        );
        self::assertSame(
            ['height' => 'Высота не может быть отрицательной'],
            FactAndRedirectActionsTest::getErrors(static fn() => new SaveMediaAction()->run(new SaveMediaRequest($media->getId(), 0, -1)))
        );
    }

    public function testSaveUnknownMedia(): void {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Media #404 not found');

        new SaveMediaAction()->run(new SaveMediaRequest(404, 1, 1));
    }

    /**
     * @param array<mixed> $content
     */
    private static function createPageRequest(
        string    $title,
        string    $slug,
        array     $content = ['blocks' => []],
        ?DateTime $publishDate = null
    ): CreateStaticPageRequest {
        return new CreateStaticPageRequest(
            title: $title,
            shortTitle: ' Коротко ',
            content: $content,
            slug: $slug,
            published: true,
            indexable: false,
            textBlock: true,
            publishDate: $publishDate,
            metaDescription: ' Описание ',
            backLinkText: ' Назад ',
            backLinkUrl: ' /back '
        );
    }

    /**
     * @return array{string, int}
     */
    private static function getTag(int $id): array {
        $tag = PhotoTag::findByUniqueIdentifier($id);
        self::assertNotNull($tag);

        return [$tag->getTitle(), $tag->getPosition()];
    }
}
