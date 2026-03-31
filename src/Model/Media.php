<?php

namespace BC\Model;

use BC\Core\Media\IThumbnailGenerator;
use BC\Core\Media\PostProcessor\IImagePostprocessor;
use BC\Core\Trait\FileSystemTrait;
use BC\Core\Trait\LoggerTrait;
use BC\Provider\IPathsProvider;
use Runway\DataStorage\Attribute as DS;
use Runway\Exception\Exception;
use Runway\FileSystem\Exception\CannotDeleteFileException;
use Runway\Model\AEntity;
use Runway\Singleton\Container;

/**
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
 * @method \BC\Model\Media|null getParent()
 * @method self setParent(\BC\Model\Media|null $parent)
 * @method string getMd5()
 * @method self setMd5(string $md5)
 */
#[DS\Table("media")]
class Media extends AEntity
{
    use LoggerTrait;
    use FileSystemTrait;

    #[DS\Id]
    protected int $id;

    #[DS\Column]
    protected string $path;

    #[DS\Column]
    protected int $width;

    #[DS\Column]
    protected int $height;

    #[DS\Column]
    protected int $size;

    #[DS\Column]
    protected string $mime;

    #[DS\Column]
    protected ?Media $parent = null;

    #[DS\Column]
    protected string $md5;

//    #[DS\Reference(refModel: self::class, refProp: "id")]
//    protected ?array $children = null;

    protected static ?string $imagesPath = null;

    /**
     * @return self[]
     */
    public function generateThumbnails(int $width): array {
        return $this->getThumbnailGenerator()->generateThumbnails($this, $width);
    }

    protected function getImagesPath(): string {
        static::$imagesPath ??= $this->getPathsProvider()->getImagesPath();

        return static::$imagesPath;
    }

    protected function getFullPath(): string {
        return "{$this->getImagesPath()}/{$this->getPath()}";
    }

    /**
     * @return self[]
     */
    public function getThumbnails(): array {
        try {
            return static::find(['parent' => $this]);
        } catch (Exception $e) {
            $this->getLogger()->warning("Cannot get thumbnails for image #{$this->getId()}: {$e->getMessage()}");

            return [];
        }
    }

    public function remove(): void
    {
        foreach ($this->getThumbnails() as $thumbnail) {
            $thumbnail->remove();
        }

        try {
            $this->getFileSystem()->remove($this->getFullPath());
        } catch (CannotDeleteFileException $e) {
            $this->getLogger()->warning("Cannot delete media file {$this->getFullPath()}: {$e->getMessage()}");
        }

        parent::remove();
    }

    protected function getPathsProvider(): IPathsProvider {
        return Container::getInstance()->getService(IPathsProvider::class);
    }

    protected function getThumbnailGenerator(): IThumbnailGenerator {
        return Container::getInstance()->getService(IThumbnailGenerator::class);
    }

    protected function getImagePostprocessor(): IImagePostprocessor {
        return Container::getInstance()->getService(IImagePostprocessor::class);
    }
}
