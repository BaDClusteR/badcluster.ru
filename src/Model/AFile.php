<?php

declare(strict_types=1);

namespace BC\Model;

use BC\Api\DTO\FileDTO;
use BC\Core\Formatter\IFormatter;
use BC\Core\Trait\FileSystemTrait;
use BC\Core\Trait\PathsProviderTrait;
use Runway\DataStorage\Attribute as DS;
use Runway\FileSystem\Exception\CannotCreateDirectoryException;
use Runway\FileSystem\Exception\CannotReadFileException;
use Runway\FileSystem\Exception\FileSystemException;
use Runway\Model\AEntity;
use Runway\Singleton\Container;

/**
 * @generated-model-helpers
 * @method int getId()
 * @method static setId(int $id)
 * @method string getMime()
 * @method static setMime(string $mime)
 * @method int getSize()
 * @method static setSize(int $size)
 * @method string getPath()
 * @method static setPath(string $path)
 * @method string getHash()
 * @method static setHash(string $hash)
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

        $mime = $mime ?: mime_content_type($filePath);
        $size = filesize($filePath);
        $hash = md5_file($filePath);

        if (($mime === false) || ($size === false) || ($hash === false)) {
            throw new CannotReadFileException($filePath);
        }

        return new static()
            ->setPath(basename($newFilename))
            ->setMime($mime)
            ->setSize($size)
            ->setHash($hash);
    }

    protected static function getFolderPath(): string {
        return static::getPathsProviderStatic()->getStaticPath() . '/' . static::getFolderRelativePath();
    }

    protected static function getFolderRelativePath(): string {
        return static::getSubfolder();
    }

    protected static function getFolderUrl(): string {
        return static::getPathsProviderStatic()->getStaticWebPath() . '/' . static::getFolderRelativePath();
    }

    public function getUrl(): string {
        return static::getFolderUrl() . '/' . $this->getPath();
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

    public function getLocalPath(): string {
        return static::getFolderPath() . '/' . $this->path;
    }

    public function remove(): void {
        try {
            $this->getFileSystem()->remove(
                $this->getLocalPath()
            );
        } catch (FileSystemException) {
        }

        parent::remove();
    }
}
