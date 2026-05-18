<?php

namespace BC\Api\Endpoint;

use ApiPlatform\Attribute as API;
use ApiPlatform\Attribute\Docs;
use ApiPlatform\DTO\ApiEndpointArgumentFileDTO;
use ApiPlatform\Exception\BadRequestException;
use BC\Core\Converter\Media\IMediaConverter;
use BC\Core\DTO\MediaDTO;
use BC\Model\Media;
use BC\Provider\IPathsProvider;
use getID3;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\FileSystem\IFileSystem;
use Runway\Logger\ILogger;
use Runway\Model\Exception\ModelException;
use Throwable;

#[Docs\Group('Files upload')]
class Upload extends AEndpoint {
    public function __construct(
        private readonly IFileSystem $fileSystem,
        private readonly IPathsProvider $pathsProvider,
        private readonly ILogger $logger,
        private readonly IMediaConverter $mediaConverter
    ) {
    }

    /**
     * @throws DBException
     * @throws ModelException
     * @throws QueryBuilderException
     * @throws BadRequestException
     */
    #[API\Endpoint(path: 'upload', method: 'POST')]
    public function uploadMedia(
        #[API\Parameter(source: 'file', name: 'file')]
        ApiEndpointArgumentFileDTO $file,
        #[API\Parameter(source: 'query', name: 'purpose')]
        ?string $purpose = null,
    ): MediaDTO {
        $mime = $file->mimeType;

        if (!in_array($mime, Media::ALLOWED_IMAGE_MIME_TYPES, true)) {
            throw new BadRequestException("Unsupported MIME type: $mime");
        }
        $folder = $this->getImagesFolder();

        $imagePath = $this->handleWithException(
            fn () => $this->fileSystem->copy(
                $file->tmpName,
                "$folder/{$this->sanitizeFilename($file->name)}",
            )
        );

        $media = $this->createModel($imagePath, $mime);

        return $this->mediaConverter->convertMedia(
            $this->doWithPurpose($media, $purpose)
        );
    }

    protected function doWithPurpose(Media $media, ?string $purpose): Media {
        if (
            $purpose === null
            && $media->isImage()
            && $media->getWidth() > 0
        ) {
            $this->tryGenerateThumbnails($media, [500, 1000, 2000]);
        }

        return $media;
    }

    /**
     * @param int[] $widths
     */
    protected function tryGenerateThumbnails(Media $media, array $widths): void {
        try {
            $media->generateThumbnails($widths);
        } catch (Throwable $e) {
            $this->logger->warning(
                "Thumbnail generation failed for media {$media->getPath()}: {$e->getMessage()}",
                [
                    'mediaPath' => $media->getPath(),
                    'widths'    => $widths
                ]
            );
        }
    }

    private function createModel(string $imagePath, string $mime): Media {
        $relativePath = "{$this->getRelativeImagesFolder()}/" . pathinfo($imagePath, PATHINFO_BASENAME);
        $size = filesize($imagePath) ?: 0;
        $md5 = md5_file($imagePath) ?: '';
        $info = @getimagesize($imagePath);
        if ($this->isVideo($mime)) {
            $info = new getID3()->analyze($imagePath);
            $width = (int) ($info['video']['resolution_x'] ?? 0);
            $height = (int) ($info['video']['resolution_y'] ?? 0);
        } else {
            $width = (int) ($info[0] ?? 0);
            $height = (int) ($info[1] ?? 0);
        }

        $media = new Media()
            ->setPath($relativePath)
            ->setWidth($width)
            ->setHeight($height)
            ->setSize($size)
            ->setMime($mime)
            ->setAlt('')
            ->setMd5($md5);

        $this->handleWithException(
            static fn () => $media->persist()
        );

        return $media;
    }

    private function isVideo(string $mime): bool {
        return str_starts_with($mime, 'video/');
    }

    private function sanitizeFilename(string $filename): string {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $base = pathinfo($filename, PATHINFO_FILENAME);
        $slug = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $base);
        $slug = trim((string) $slug, '-') ?: 'file';

        return "$slug.$ext";
    }

    private function getImagesFolder(): string {
        $imagesRoot = $this->pathsProvider->getImagesPath();

        $relativePath = $this->getRelativeImagesFolder();
        $result = "$imagesRoot/$relativePath";
        $this->handleWithException(
            fn () => $this->fileSystem->mkdir($result)
        );

        return $result;
    }

    private function getRelativeImagesFolder(): string {
        return date('Y');
    }
}
