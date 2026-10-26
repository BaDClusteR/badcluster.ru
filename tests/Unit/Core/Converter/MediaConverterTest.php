<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Core\Converter;

use BC\Core\Converter\Media\MediaConverter;
use BC\Core\DTO\MediaDTO;
use BC\Core\DTO\MediaThumbnailDTO;
use BC\Model\Media;
use BC\Tests\Support\SqliteSchema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MediaConverter::class)]
#[CoversClass(MediaDTO::class)]
#[CoversClass(MediaThumbnailDTO::class)]
final class MediaConverterTest extends TestCase {
    protected function setUp(): void {
        SqliteSchema::createModelTables(Media::class);
    }

    public function testNull(): void {
        self::assertNull(new MediaConverter()->convertMedia(null));
    }

    public function testMediaWithThumbnails(): void {
        $media = self::createMedia('2024/photo.jpg', 1600, 900, 'image/jpeg', 'Фото');
        $small = self::createMedia('2024/photo-320.webp', 320, 180, 'image/webp', '', $media);
        $big = self::createMedia('2024/photo-800.avif', 800, 450, 'image/avif', '', $media);
        self::createMedia('2024/other.jpg', 10, 10, 'image/jpeg', '');

        $dto = new MediaConverter()->convertMedia(Media::findByUniqueIdentifier($media->getId()));

        self::assertNotNull($dto);
        self::assertSame(
            [
                'id'     => $media->getId(),
                'url'    => 'http://bc.test/static/media/2024/photo.jpg',
                'width'  => 1600,
                'height' => 900,
                'mime'   => 'image/jpeg',
                'alt'    => 'Фото',
                'thumbs' => [
                    [
                        'id'     => $small->getId(),
                        'width'  => 320,
                        'height' => 180,
                        'mime'   => 'image/webp',
                        'url'    => 'http://bc.test/static/media/2024/photo-320.webp',
                    ],
                    [
                        'id'     => $big->getId(),
                        'width'  => 800,
                        'height' => 450,
                        'mime'   => 'image/avif',
                        'url'    => 'http://bc.test/static/media/2024/photo-800.avif',
                    ],
                ],
            ],
            $dto->toArray()
        );
    }

    public function testMediaWithoutThumbnails(): void {
        $media = self::createMedia('clip.mp4', 1920, 1080, 'video/mp4', 'Клип');

        $dto = new MediaConverter()->convertMedia($media);

        self::assertNotNull($dto);
        self::assertSame([], $dto->thumbs);
        self::assertSame('http://bc.test/static/media/clip.mp4', $dto->url);
    }

    public static function createMedia(
        string $path,
        int    $width,
        int    $height,
        string $mime,
        string $alt,
        ?Media $parent = null
    ): Media {
        $media = new Media()
            ->setPath($path)
            ->setWidth($width)
            ->setHeight($height)
            ->setMime($mime)
            ->setAlt($alt)
            ->setSize(1000)
            ->setMd5(md5($path))
            ->setParent($parent);
        $media->persist();

        return $media;
    }
}
