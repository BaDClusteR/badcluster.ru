<?php

namespace BC\Api\Endpoint;

use ApiPlatform\Attribute as API;
use ApiPlatform\Attribute\Docs;
use ApiPlatform\DTO\ApiEndpointArgumentFileDTO;
use ApiPlatform\Exception\BadRequestException;
use ApiPlatform\Exception\InternalErrorException;
use ApiPlatform\Exception\RuntimeInternalErrorException;
use BC\Api\DTO\BlogPostDTO;
use BC\Api\DTO\BlogPostsDTO;
use BC\Api\DTO\MediaDTO;
use BC\Api\DTO\MediaThumbnailDTO;
use BC\Api\Enum\BlogPostStatusEnum;
use BC\Core\Converter\IConverter;
use BC\Model\Media;
use BC\Model\Post;
use BC\Provider\IPathsProvider;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\FileSystem\IFileSystem;
use Runway\Logger\ILogger;
use Runway\Model\Exception\ModelException;
use Runway\Singleton\Container;
use Throwable;

#[Docs\Group("Files upload")]
class Upload extends AEndpoint
{
    public function __construct(
        private readonly IFileSystem $fileSystem,
        private readonly IPathsProvider $pathsProvider,
        private readonly ILogger $logger
    ) {
    }

    /**
     * @throws DBException
     * @throws ModelException
     * @throws QueryBuilderException
     * @throws BadRequestException
     */
    #[API\Endpoint(path: "upload", method: "POST")]
    public function uploadMedia(
        #[API\Parameter(source: "file", name: "file")]
        ApiEndpointArgumentFileDTO $file
    ): MediaDTO {
        $mime = $file->mimeType;

        if (!in_array($mime, Media::ALLOWED_IMAGE_MIME_TYPES, true)) {
            throw new BadRequestException("Unsupported MIME type: $mime");
        }
        $folder = $this->getImagesFolder();

        $imagePath = $this->handleWithException(
            fn() => $this->fileSystem->copy(
                $file->tmpName,
                "$folder/{$this->sanitizeFilename($file->name)}",
            )
        );

        $media = $this->createModel($imagePath, $mime);

        if ($media->getWidth() > 0) {
            foreach ([500, 1000, 2000] as $thumbWidth) {
                try {
                    $media->generateThumbnails($thumbWidth);
                } catch (Throwable $e) {
                    $this->logger->warning("Thumbnail generation failed for width $thumbWidth: {$e->getMessage()}");
                }
            }
        }

        return $this->convertModel($media);
    }

    private function convertModel(Media $media): MediaDTO {
        return new MediaDTO(
            id: $media->getId(),
            url: $media->getWebPath(),
            width: $media->getWidth(),
            height: $media->getHeight(),
            mime: $media->getMime(),
            alt: $media->getAlt(),
            thumbs: array_map(
                fn (Media $thumbnail): MediaThumbnailDTO => $this->buildThumbnail($thumbnail),
                $media->getThumbnails()
            )
        );
    }

    private function buildThumbnail(Media $thumbnail): MediaThumbnailDTO {
        return new MediaThumbnailDTO(
            width: $thumbnail->getWidth(),
            height: $thumbnail->getHeight(),
            mime: $thumbnail->getMime(),
            url: $thumbnail->getWebPath()
        );
    }

    private function createModel(string $imagePath, string $mime): Media {
        $relativePath = "{$this->getRelativeImagesFolder()}/" . pathinfo($imagePath, PATHINFO_BASENAME);
        $size = filesize($imagePath) ?: 0;
        $md5 = md5_file($imagePath) ?: '';
        $info = @getimagesize($imagePath);
        $width = (int)($info[0] ?? 0);
        $height = (int)($info[1] ?? 0);

        $media = new Media()
            ->setPath($relativePath)
            ->setWidth($width)
            ->setHeight($height)
            ->setSize($size)
            ->setMime($mime)
            ->setAlt('')
            ->setMd5($md5);

        $this->handleWithException(
            static fn() => $media->persist()
        );

        return $media;
    }

    private function sanitizeFilename(string $filename): string {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $base = pathinfo($filename, PATHINFO_FILENAME);
        $slug = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $base);
        $slug = trim((string)$slug, '-') ?: 'file';

        return "$slug.$ext";
    }

    private function getImagesFolder(): string {
        $imagesRoot = $this->pathsProvider->getImagesPath();

        $relativePath = $this->getRelativeImagesFolder();
        $result = "$imagesRoot/$relativePath";
        $this->handleWithException(
            fn() => $this->fileSystem->mkdir($result)
        );

        return $result;
    }

    private function getRelativeImagesFolder(): string {
        return date('Y');
    }
}
