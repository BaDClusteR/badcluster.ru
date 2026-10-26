<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Core\Helper;

use BC\Core\Converter\Media\MediaConverter;
use BC\Core\Helper\BlockHelper;
use BC\Model\Media;
use BC\Tests\Support\ArrayPath;
use BC\Tests\Support\SqliteSchema;
use BC\Tests\Unit\Core\Converter\MediaConverterTest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * The blocks of Editor.js: the media are stored as IDs (cleanBlocks) and get their data back for the editor
 * (enrichBlocks).
 */
#[CoversClass(BlockHelper::class)]
final class BlockHelperTest extends TestCase {
    use ArrayPath;

    private BlockHelper $helper;

    protected function setUp(): void {
        SqliteSchema::createModelTables(Media::class);
        $this->helper = new BlockHelper(new MediaConverter());
    }

    public function testCleanBlocksKeepsOnlyMediaIdsAndSavesTheirData(): void {
        $media = MediaConverterTest::createMedia('a.jpg', 100, 50, 'image/jpeg', '');
        $slide = MediaConverterTest::createMedia('b.jpg', 10, 20, 'image/jpeg', 'old');

        $content = [
            'time'   => 1,
            'blocks' => [
                ['type' => 'paragraph', 'data' => ['text' => 'Текст']],
                [
                    'type' => 'media',
                    'data' => [
                        'media'   => ['id' => $media->getId(), 'url' => 'http://x/a.jpg', 'alt' => 'Новый alt', 'width' => 640, 'height' => 480],
                        'caption' => 'Подпись',
                    ],
                ],
                [
                    'type' => 'gallery',
                    'data' => [
                        'slides' => [
                            ['id' => $slide->getId(), 'url' => 'http://x/b.jpg', 'alt' => 'Слайд', 'width' => 30, 'height' => 40],
                            ['url' => 'http://external/c.jpg'],
                        ],
                    ],
                ],
            ],
        ];

        $cleaned = $this->helper->cleanBlocks($content);

        self::assertSame(1, $cleaned['time']);
        self::assertSame($content['blocks'][0], self::at($cleaned, 'blocks', 0));
        self::assertSame(['media' => ['id' => $media->getId()], 'caption' => 'Подпись'], self::at($cleaned, 'blocks', 1, 'data'));
        self::assertSame(
            ['slides' => [['id' => $slide->getId()], ['url' => 'http://external/c.jpg']]],
            self::at($cleaned, 'blocks', 2, 'data')
        );

        $media = Media::findByUniqueIdentifier($media->getId());
        self::assertNotNull($media);
        self::assertSame(['Новый alt', 640, 480], [$media->getAlt(), $media->getWidth(), $media->getHeight()]);

        $slide = Media::findByUniqueIdentifier($slide->getId());
        self::assertNotNull($slide);
        self::assertSame(['Слайд', 30, 40], [$slide->getAlt(), $slide->getWidth(), $slide->getHeight()]);
    }

    public function testCleanBlocksOfUnknownMedia(): void {
        $content = ['blocks' => [['type' => 'media', 'data' => ['media' => ['id' => 404, 'alt' => 'x', 'width' => 1, 'height' => 1]]]]];

        self::assertSame(
            ['blocks' => [['type' => 'media', 'data' => ['media' => ['id' => 404]]]]],
            $this->helper->cleanBlocks($content)
        );
        self::assertSame([], Media::find());
    }

    public function testCleanBlocksWithoutMedia(): void {
        $content = [
            'blocks' => [
                ['type' => 'media', 'data' => ['media' => []]],
                ['type' => 'media', 'data' => []],
                ['type' => 'gallery', 'data' => ['slides' => []]],
                ['type' => 'header', 'data' => ['media' => ['id' => 1, 'url' => 'kept']]],
                ['data' => ['media' => ['id' => 1, 'url' => 'kept']]],
            ],
        ];

        self::assertSame($content, $this->helper->cleanBlocks($content));
        self::assertSame([], $this->helper->cleanBlocks([]));
        self::assertSame(['blocks' => []], $this->helper->cleanBlocks(['blocks' => []]));
    }

    public function testSlidesOfOtherBlocksAreKept(): void {
        $media = MediaConverterTest::createMedia('a.jpg', 100, 50, 'image/jpeg', 'Alt');
        $content = ['time' => 5, 'blocks' => [['type' => 'carousel', 'data' => ['slides' => [['id' => $media->getId(), 'url' => 'x']]]]]];

        self::assertSame($content, $this->helper->cleanBlocks($content));
        self::assertSame($content, $this->helper->enrichBlocks($content));
    }

    public function testInvalidBlocksAreKept(): void {
        self::assertSame(['blocks' => 'garbage'], $this->helper->cleanBlocks(['blocks' => 'garbage']));
        self::assertSame(['blocks' => 'garbage'], $this->helper->enrichBlocks(['blocks' => 'garbage']));
    }

    public function testEnrichBlocks(): void {
        $media = MediaConverterTest::createMedia('a.jpg', 100, 50, 'image/jpeg', 'Alt');
        $slide = MediaConverterTest::createMedia('b.jpg', 10, 20, 'image/png', 'Slide');
        $thumb = MediaConverterTest::createMedia('b-5.webp', 5, 10, 'image/webp', '', $slide);

        $content = [
            'time'    => 7,
            'blocks'  => [
                ['type' => 'paragraph', 'data' => ['text' => 'Текст']],
                ['type' => 'media', 'data' => ['media' => ['id' => $media->getId()], 'caption' => 'C']],
                ['type' => 'media', 'data' => ['media' => ['id' => 404]]],
                ['type' => 'gallery', 'data' => ['slides' => [['id' => $slide->getId()], ['id' => 404], ['url' => 'x']]]],
            ],
        ];

        $enriched = $this->helper->enrichBlocks($content);

        self::assertSame(7, $enriched['time']);
        self::assertSame($content['blocks'][0], self::at($enriched, 'blocks', 0));
        self::assertSame(
            [
                'media'   => [
                    'id'     => $media->getId(),
                    'url'    => 'http://bc.test/static/media/a.jpg',
                    'width'  => 100,
                    'height' => 50,
                    'mime'   => 'image/jpeg',
                    'alt'    => 'Alt',
                    'thumbs' => [],
                ],
                'caption' => 'C',
            ],
            self::at($enriched, 'blocks', 1, 'data')
        );
        self::assertSame(['media' => ['id' => 404]], self::at($enriched, 'blocks', 2, 'data'), 'An unknown media stays as is.');
        self::assertSame(
            [
                [
                    'id'     => $slide->getId(),
                    'url'    => 'http://bc.test/static/media/b.jpg',
                    'width'  => 10,
                    'height' => 20,
                    'mime'   => 'image/png',
                    'alt'    => 'Slide',
                    'thumbs' => [
                        ['id' => $thumb->getId(), 'width' => 5, 'height' => 10, 'mime' => 'image/webp', 'url' => 'http://bc.test/static/media/b-5.webp'],
                    ],
                ],
                ['id' => 404],
                ['url' => 'x'],
            ],
            self::at($enriched, 'blocks', 3, 'data', 'slides')
        );
    }

    public function testEnrichBlocksWithoutMedia(): void {
        $content = [
            'blocks' => [
                ['type' => 'media', 'data' => ['media' => []]],
                ['type' => 'gallery', 'data' => []],
                ['type' => 'quote', 'data' => ['media' => ['id' => 1]]],
            ],
        ];

        self::assertSame($content, $this->helper->enrichBlocks($content));
        self::assertSame([], $this->helper->enrichBlocks([]));
    }
}
