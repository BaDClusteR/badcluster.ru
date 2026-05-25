<?php

declare(strict_types=1);

namespace BC\Controller;

use BC\Core\Response\HtmlResponse;
use BC\Model\Media;
use BC\Provider\Admin\IAppSettingsProvider;
use BC\Provider\IPathsProvider;
use JsonException;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Logger\ILogger;
use Runway\Model\Exception\ModelException;
use Runway\Request\IRequest;
use Runway\Request\Response;
use Runway\Singleton\Container;
use Throwable;

readonly class Admin {
    public function __construct(
        private IRequest $request,
        private ILogger $logger,
        private IAppSettingsProvider $appSettingsProvider,
    ) {
    }

    public function index(): Response {
        return new HtmlResponse(
            200,
            new \BC\Widget\Admin()->render([
                'devMode'     => $this->isInDevMode(),
                'webRoot'     => 'http://localhost:5173/static/app',
                'appSettings' => $this->appSettingsProvider->getAppSettings()
            ])
        );
    }

    private function isInDevMode(): bool {
        return $this->request->getGetParameter('dev')->asString() === '1'
            || file_exists(PROJECT_ROOT . '/app/node_modules/.vite');
    }

    /**
     * Accepts a single file upload, stores it under static/images/{year}/,
     * creates a Media record, generates thumbnails for images, and returns
     * a JSON payload shaped for the admin app to render a <picture>/<video>.
     */
    /**
     * @throws DBException
     * @throws QueryBuilderException
     * @throws ModelException
     */
    public function mediaUpload(): Response {
        $file = $this->request->getFile('file');
        if (!$file) {
            return $this->jsonResponse(400, ['error' => 'No file uploaded']);
        }

        $mime = $file->getType();
        $isImage = str_starts_with($mime, 'image/');
        $isVideo = str_starts_with($mime, 'video/');
        if (!$isImage && !$isVideo) {
            return $this->jsonResponse(400, ['error' => "Unsupported MIME type: $mime"]);
        }

        /** @var IPathsProvider $paths */
        $paths = Container::getInstance()->getService(IPathsProvider::class);
        $imagesRoot = $paths->getImagesPath();

        $year = date('Y');
        $yearDir = "$imagesRoot/$year";
        if (!is_dir($yearDir) && !mkdir($yearDir, 0775, true) && !is_dir($yearDir)) {
            return $this->jsonResponse(500, ['error' => 'Cannot create upload directory']);
        }

        // Build a filesystem-safe filename
        $originalName = $file->getName();
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $base = pathinfo($originalName, PATHINFO_FILENAME);
        $slug = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $base);
        $slug = trim((string) $slug, '-') ?: 'file';

        $filename = "$slug.$ext";
        $target = "$yearDir/$filename";
        $i = 1;
        while (file_exists($target) && $i < 10000) {
            $filename = "$slug-$i.$ext";
            $target = "$yearDir/$filename";
            $i++;
        }

        if (file_exists($target)) {
            return $this->jsonResponse(500, ['error' => 'Failed to save upload']);
        }

        if (!@copy($file->getTmpName(), $target)) {
            return $this->jsonResponse(500, ['error' => 'Failed to save upload']);
        }

        $relativePath = "$year/$filename";
        $size = filesize($target) ?: 0;
        $md5 = md5_file($target) ?: '';

        $width = 0;
        $height = 0;
        if ($isImage) {
            $info = @getimagesize($target);
            if ($info) {
                $width = (int) $info[0];
                $height = (int) $info[1];
            }
        }

        // Persist Media record
        $media = new Media()
            ->setPath($relativePath)
            ->setWidth($width)
            ->setHeight($height)
            ->setSize($size)
            ->setMime($mime)
            ->setAlt('')
            ->setMd5($md5);
        $media->persist();

        // Generate thumbnails for images at standard widths.
        // generateThumbnails(width) is idempotent per (width, mime) pair.
        if ($isImage && $width > 0) {
            try {
                $media->generateThumbnails([500, 1000]);
            } catch (Throwable $e) {
                $this->logger->warning("Thumbnail generation failed for {$media->getPath()}: {$e->getMessage()}");
            }
        }

        return $this->jsonResponse(200, $this->serializeMedia($media));
    }

    /**
     * Serialize a Media record (with its thumbnails) into the JSON shape
     * consumed by the admin app's MediaBlock and Picture components.
     */
    private function serializeMedia(Media $media): array {
        $thumbs = [];
        foreach ($media->getThumbnails() as $t) {
            $thumbs[] = [
                'width'  => $t->getWidth(),
                'height' => $t->getHeight(),
                'mime'   => $t->getMime(),
                'url'    => $t->getWebPath(),
            ];
        }

        return [
            'id'     => $media->getId(),
            'url'    => $media->getWebPath(),
            'width'  => $media->getWidth(),
            'height' => $media->getHeight(),
            'mime'   => $media->getMime(),
            'alt'    => $media->getAlt(),
            'type'   => str_starts_with($media->getMime(), 'video/') ? 'video' : 'image',
            'thumbs' => $thumbs,
        ];
    }

    private function jsonResponse(int $code, array $data): Response {
        try {
            $encoded = json_encode($data, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $encoded = '{}';
        }

        return new HtmlResponse(
            $code,
            $encoded,
            ['Content-Type' => 'application/json']
        );
    }
}
