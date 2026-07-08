<?php

declare(strict_types=1);

namespace BC\Core\Action\Media;

use BC\Core\Trait\FileSystemTrait;
use BC\Model\Media;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;
use Runway\FileSystem\Exception\CannotCreateDirectoryException;
use Runway\FileSystem\Exception\FileSystemException;

/**
 * База для экшенов над медиа-моделями с собственной таблицей и папкой
 * (Screenshot, Photo и т.п.).
 */
abstract class AMediaAction {
    use FileSystemTrait;

    /** Ширины тамбнейлов, которые генерируются для каждой картинки. */
    protected const array THUMBNAIL_WIDTHS = [500, 1000, 2000];

    /**
     * Принудительно генерирует тамбнейлы. Postprocess отключен,
     * чтобы постпроцессоры не удаляли тамбнейлы по своим критериям.
     */
    protected function generateThumbnails(Media $model): void {
        $model->tryGenerateThumbnails(static::THUMBNAIL_WIDTHS, force: true, postprocess: false);
    }

    /**
     * @throws ActionValidationException
     */
    protected function validateMedia(Media $media): void {
        if (!$media->isImage()) {
            throw new ActionValidationException(['image' => 'Файл не является изображением']);
        }
    }

    /**
     * Копирует файл загруженной медиа в папку целевой модели и заполняет
     * ее файловые поля из медиа. Сама медиа при этом не удаляется —
     * это делает вызывающий код после успешного persist() целевой модели.
     *
     * @throws CannotCreateDirectoryException
     * @throws FileSystemException
     */
    protected function attachMediaFile(Media $target, Media $media): void {
        $targetRoot = $target::getSubfolderPath();

        $relativeDir = dirname($media->getPath());
        $dstDir = $relativeDir !== '.'
            ? "$targetRoot/$relativeDir"
            : $targetRoot;

        $fileSystem = $this->getFileSystem();
        $fileSystem->mkdir($dstDir);

        $newPath = $fileSystem->copy(
            $media->getLocalPath(),
            $dstDir . '/' . basename($media->getPath())
        );

        $target->setPath(mb_substr($newPath, mb_strlen("$targetRoot/")))
               ->setWidth($media->getWidth())
               ->setHeight($media->getHeight())
               ->setSize($media->getSize())
               ->setMime($media->getMime())
               ->setMd5($media->getMd5());
    }
}
