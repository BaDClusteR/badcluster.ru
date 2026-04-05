<?php

namespace BC\Core\Media;

use BC\Core\Exception\ImageException;
use BC\Core\Media\Processor\IImageProcessor;
use BC\Model\Media;
use BC\Provider\IPathsProvider;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Exception\Exception;
use Runway\Logger\ILogger;
use Runway\Model\Exception\ModelException;
use Runway\Singleton\Container;

readonly class ThumbnailGenerator implements IThumbnailGenerator
{
    public function __construct(
        private IPathsProvider $pathProvider,
        private ILogger $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function generateThumbnails(Media $image, int $width, bool $force = false): array
    {
        $result = [];
        $imagesPath = $this->pathProvider->getImagesPath();
        $fullPath = "$imagesPath/{$image->getPath()}";
        $sourceWidth = $image->getWidth();

        /** @var IImageProcessor[] $processors */
        $processors = Container::getInstance()->getServicesByTag('image.processor');
        foreach ($processors as $processor) {
            try {
                $width = min($width, $sourceWidth);

                $thumbnail = $image->getThumbnail($width, $processor->getGeneratedMimeType());
                if ($thumbnail) {
                    if ($force) {
                        $thumbnail->remove();
                    } else {
                        continue;
                    }
                }

                $thumbnailDTO = null;
                if ($processor->isApplicable($fullPath)) {
                    $thumbnailDTO = $processor->getThumbnail(
                        $fullPath,
                        $width,
                        $sourceWidth
                    );
                }

                if (!$thumbnailDTO) {
                    continue;
                }

                try {
                    $path = mb_substr(
                        $thumbnailDTO->path,
                        mb_strlen("$imagesPath/")
                    );

                    $model = new Media()
                        ->setPath($path)
                        ->setWidth($thumbnailDTO->width)
                        ->setHeight($thumbnailDTO->height)
                        ->setMime($thumbnailDTO->mime)
                        ->setMd5($thumbnailDTO->md5)
                        ->setSize(filesize($thumbnailDTO->path))
                        ->setParent($image);

                    $model->persist();
                    $result[] = $model;
                } catch (Exception $e) {
                    $this->logger->warning(
                        "Cannot write metadata for generated image in DB: {$e->getMessage()}",
                        [
                            'width' => $width,
                            'path'  => $fullPath
                        ]
                    );
                }
            } catch (Exception $e) {
                $this->logger->warning(
                    "Cannot generate thumbnail for $fullPath",
                    [
                        'path'    => $fullPath,
                        'code'    => $e->getCode(),
                        'message' => $e->getMessage(),
                    ]
                );
            }
        }

        return $result;
    }
}
