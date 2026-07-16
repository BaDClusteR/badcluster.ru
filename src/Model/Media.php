<?php

declare(strict_types=1);

namespace BC\Model;

use BC\Core\DTO\MediaDTO;
use BC\Core\DTO\MediaThumbnailDTO;
use BC\Core\Media\IThumbnailGenerator;
use BC\Core\Trait\FileSystemTrait;
use BC\Core\Trait\LoggerTrait;
use BC\Core\Trait\PathsProviderTrait;
use BC\Generator\IThumbnailsGenerator;
use Runway\DataStorage\Attribute as DS;
use Runway\Exception\Exception;
use Runway\FileSystem\Exception\CannotDeleteFileException;
use Runway\Model\AEntity;
use Runway\Singleton\Container;
use Throwable;

/**
 * @generated-model-helpers
 * @method int getId()
 * @method self setId(int $id)
 * @method string getPath()
 * @method self setPath(string $path)
 * @method int getWidth()
 * @method self setWidth(int $width)
 * @method int getHeight()
 * @method self setHeight(int $height)
 * @method int getSize()
 * @method self setSize(int $size)
 * @method string getMime()
 * @method self setMime(string $mime)
 * @method string getAlt()
 * @method self setAlt(string $alt)
 * @method Media|null getParent()
 * @method self setParent(Media|null $parent)
 * @method string getMd5()
 * @method self setMd5(string $md5)
 */
#[DS\Table('media')]
class Media extends AEntity {
    use LoggerTrait;
    use FileSystemTrait;
    use PathsProviderTrait;

    public const array ALLOWED_IMAGE_MIME_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
        'image/avif',
        'video/mp4',
        'video/webm',
    ];

    /**
     * Каноничное расширение по MIME. Ключи — то, что реально возвращает
     * finfo (image/jpg, например, не вернётся никогда), значения — расширение,
     * которое присваивается загруженному файлу.
     */
    public const array MIME_TO_EXTENSION = [
        'image/jpeg' => 'jpg',
        'image/jpg'  => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/avif' => 'avif',
        'video/mp4'  => 'mp4',
        'video/webm' => 'webm',
    ];

    #[DS\Id]
    protected int $id;

    #[DS\Column]
    protected string $path = '';

    #[DS\Column]
    protected int $width = 0;

    #[DS\Column]
    protected int $height = 0;

    #[DS\Column]
    protected int $size = 0;

    #[DS\Column]
    protected string $mime = '';

    #[DS\Column]
    protected string $alt = '';

    #[DS\Column]
    protected ?Media $parent = null;

    #[DS\Column]
    protected string $md5;

    //    #[DS\Reference(refModel: self::class, refProp: "id")]
    //    protected ?array $children = null;

    protected static ?string $imagesPath = null;

    public static function getSubfolder(): string {
        return 'media';
    }

    public static function getSubfolderPath(): string {
        return static::getPathsProviderStatic()->getStaticPath() . '/' . static::getSubfolder();
    }

    public static function getSubfolderUrl(): string {
        return static::getPathsProviderStatic()->getStaticWebPath() . '/' . static::getSubfolder();
    }

    /**
     * @return self[][]
     */
    public function generateThumbnails(array $widths, bool $force = false, bool $postprocess = true): array {
        return $this->getThumbnailsGenerator()->generateThumbnails($this, $widths, $force, $postprocess);
    }

    /**
     * @param int[] $widths
     */
    public function tryGenerateThumbnails(array $widths, bool $force = false, bool $postprocess = true): void {
        try {
            $this->generateThumbnails($widths, $force, $postprocess);
        } catch (Throwable $e) {
            $this->getLogger()->warning(
                "Thumbnail generation failed for media {$this->getPath()}: {$e->getMessage()}",
                [
                    'mediaPath' => $this->getPath(),
                    'widths'    => $widths,
                ]
            );
        }
    }

    public function generateThumbnail(int $width, bool $force = false): array {
        return $this->getThumbnailGenerator()->generateThumbnails($this, $width, $force);
    }

    /**
     * @return self[]
     */
    public function getThumbnails(): array {
        try {
            // parent_id не выбираем намеренно: при гидрации ссылка на родителя
            // резолвится по объявленному типу (Media), и для наследников с другой
            // таблицей (Screenshot) родитель ищется не в той таблице
            $columns = array_map(
                static fn ($prop): string => $prop->getColumn(),
                array_filter(
                    $this->getProps(),
                    static fn ($prop): bool => $prop->getPropName() !== 'parent'
                )
            );

            return static::getQueryBuilder()
                         ->select(implode(', ', $columns))
                         ->where('parent_id = :parentId')
                         ->setVariable('parentId', $this->getId())
                         ->getEntities();
        } catch (Exception $e) {
            $this->getLogger()->warning("Cannot get thumbnails for image #{$this->getId()}: {$e->getMessage()}");

            return [];
        }
    }

    public function getThumbnail(int $width, string $mimeType): ?self {
        return array_find(
            $this->getThumbnails(),
            static fn (self $image): bool => ($image->getWidth() === $width) && ($image->getMime() === $mimeType)
        );
    }

    public function remove(): void {
        foreach ($this->getThumbnails() as $thumbnail) {
            $thumbnail->remove();
        }

        try {
            $this->getFileSystem()->remove($this->getLocalPath());
        } catch (CannotDeleteFileException $e) {
            $this->getLogger()->warning("Cannot delete media file {$this->getLocalPath()}: {$e->getMessage()}");
        }

        parent::remove();
    }

    public function isVideo(): bool {
        return str_starts_with($this->mime, 'video/');
    }

    public function isImage(): bool {
        return str_starts_with($this->mime, 'image/');
    }

    public function getWebPath(): string {
        return static::getSubfolderUrl() . '/' . $this->getPath();
    }

    public function getLocalPath(): string {
        return static::getSubfolderPath() . '/' . $this->getPath();
    }

    protected function getThumbnailsGenerator(): IThumbnailsGenerator {
        return Container::getInstance()->getService(IThumbnailsGenerator::class);
    }

    protected function getThumbnailGenerator(): IThumbnailGenerator {
        return Container::getInstance()->getService(IThumbnailGenerator::class);
    }

    public function toMediaDTO(): MediaDTO {
        return new MediaDTO(
            id: $this->id,
            url: $this->getWebPath(),
            width: $this->width,
            height: $this->height,
            mime: $this->mime,
            alt: $this->alt,
            thumbs: array_map(
                static fn (Media $thumb): MediaThumbnailDTO => new MediaThumbnailDTO(
                    id: $thumb->getId(),
                    width: $thumb->getWidth(),
                    height: $thumb->getHeight(),
                    mime: $thumb->getMime(),
                    url: $thumb->getWebPath()
                ),
                $this->getThumbnails()
            )
        );
    }
}
