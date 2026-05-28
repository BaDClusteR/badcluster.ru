<?php

declare(strict_types=1);

namespace BC\Model;

use BC\Api\DTO\FileDTO;
use BC\Core\Formatter\IFormatter;
use BC\Core\Trait\FileSystemTrait;
use BC\Core\Trait\PathsProviderTrait;
use Runway\DataStorage\Attribute as DS;
use Runway\FileSystem\Exception\CannotCreateDirectoryException;
use Runway\FileSystem\Exception\FileSystemException;
use Runway\Model\AEntity;
use Runway\Singleton\Container;

/**
 * @generated-model-helpers
 * @method int getId
 * @method self setId(int $id)
 * @method string getMime
 * @method self setMime(string $mime)
 * @method int getSize
 * @method self setSize(int $size)
 * @method string getPath
 * @method self setPath(string $path)
 * @method string getHash
 * @method self setHash(string $hash)
 */
abstract class AFile extends AEntity {
    use PathsProviderTrait;
    use FileSystemTrait;

    #[DS\Id]
    protected int $id;

    #[DS\Column]
    protected string $mime = '';

    #[DS\Column]
    protected int $size = 0;

    #[DS\Column]
    protected string $path = '';

    #[DS\Column]
    protected string $hash = '';

    protected static function getSubfolder(): string {
        return 'files';
    }

    /**
     * @throws FileSystemException
     * @throws CannotCreateDirectoryException
     */
    public static function createFrom(string $filePath, string $filename, ?string $mime = null): static {
        $fileSystem = static::getFileSystemStatic();
        $dir = static::getPathsProviderStatic()->getStaticPath() . '/' . static::getFolderRelativePath();

        $fileSystem->mkdir($dir);
        $newFilename = $fileSystem->copy($filePath, "$dir/$filename");

        return new static()
            ->setPath(static::getFolderRelativePath() . '/' . basename($newFilename))
            ->setMime($mime ?: mime_content_type($filePath))
            ->setSize(filesize($filePath))
            ->setHash(md5_file($filePath));
    }

    protected static function getFolderPath(): string {
        return static::getPathsProviderStatic()->getStaticPath() . '/' . static::getFolderRelativePath();
    }

    protected static function getFolderRelativePath(): string {
        return static::getSubfolder() . '/' . date('Y');
    }

    public function getUrl(): string {
        return $this->getPathsProvider()->getStaticWebPath() . '/' . $this->getPath();
    }

    public function toFileDTO(): FileDTO {
        $formatter = Container::getInstance()->getService(IFormatter::class);

        return new FileDTO(
            id: $this->id,
            filename: basename($this->path),
            size: $this->size,
            sizeHumanReadable: $formatter->formatAsHumanReadableSize($this->size),
            mime: $this->mime,
            url: $this->getUrl()
        );
    }

    public function remove(): void {
        try {
            $this->getFileSystem()->remove(
                $this->getPathsProvider()->getStaticPath() . '/' . static::getSubfolder() . '/' . $this->path
            );
        } catch (FileSystemException) {
        }

        parent::remove();
    }
}
