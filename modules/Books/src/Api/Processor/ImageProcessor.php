<?php

namespace BC\Modules\Books\Api\Processor;

use BC\Api\Processor\IImageProcessor;
use BC\Core\Exception\ImageException;
use BC\Model\Media;
use BC\Modules\Books\Core\Media\Processor\ImageProcessorJpg;
use BC\Provider\IPathsProvider;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Exception\Exception;
use Runway\Logger\ILogger;
use Runway\Model\Exception\ModelException;

readonly class ImageProcessor implements IImageProcessor {
    public function __construct(
        private IImageProcessor $inner,
        private ImageProcessorJpg $jpgProcessor,
        private IPathsProvider $pathProvider,
        protected ILogger $logger
    ) {
    }

    public function process(Media $image, ?string $purpose): Media {
        if ($purpose === 'book_cover') {
            $image->tryGenerateThumbnails([260]);

            $imagesPath = $this->pathProvider->getImagesPath();

            try {
                $thumbnail = $this->jpgProcessor->getThumbnail(
                    "$imagesPath/{$image->getPath()}",
                    1000,
                    $image->getWidth()
                );

                $path = mb_substr(
                    $thumbnail->path,
                    mb_strlen("$imagesPath/")
                );

                new Media()
                    ->setParent($image)
                    ->setSize($thumbnail->size)
                    ->setPath($path)
                    ->setMime($thumbnail->mime)
                    ->setWidth($thumbnail->width)
                    ->setHeight($thumbnail->height)
                    ->setMd5($thumbnail->md5)
                    ->persist();
            } catch (Exception $e) {
                $this->logger->error(
                    "Cannot generate thumbnail for the book cover: {$e->getMessage()}",
                    [
                        'path' => $image->getPath(),
                        'id'   => $image->getId(),
                    ]
                );
            }

            return $image;
        }

        if ($purpose === 'book_cover_bg') {
            $image->tryGenerateThumbnails([130]);
        }

        if ($purpose === 'chapter') {
            $image->tryGenerateThumbnails([500, 1000, 2000]);

            return $image;
        }

        return $this->inner->process($image, $purpose);
    }
}
