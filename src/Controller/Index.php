<?php

namespace BC\Controller;

use BC\Core\Response\HtmlResponse;
use BC\Model\Media;
use BC\Provider\IPathsProvider;
use BC\Widget\Page\Home;
use Runway\Request\Response;
use Runway\Singleton\Container;

class Index extends AController
{
    public function test(): Response
    {
        return new HtmlResponse(
            200,
            new Home()->render()
        );
    }

    public function adminTest(): Response
    {
        $isDev = $this->request->getGetParameter('dev')->asString() === '1'
            || file_exists(__DIR__ . '/../../src/app/node_modules/.vite');

        if ($isDev) {
            $scripts = <<<HTML
                <script type="module">
                    import RefreshRuntime from "http://localhost:5173/static/app/@react-refresh";
                    RefreshRuntime.injectIntoGlobalHook(window);
                    window.\$RefreshReg\$ = () => {};
                    window.\$RefreshSig\$ = () => (type) => type;
                    window.__vite_plugin_react_preamble_installed__ = true;
                </script>
                <script type="module" src="http://localhost:5173/static/app/@vite/client"></script>
                <script type="module" src="http://localhost:5173/static/app/src/main.tsx"></script>
            HTML;
        } else {
            $manifest = @file_get_contents(__DIR__ . '/../../static/app/.vite/manifest.json');
            $entry = '';
            $css = '';
            if ($manifest) {
                $data = json_decode($manifest, true);
                $main = $data['index.html'] ?? $data['src/main.tsx'] ?? [];
                $entry = '/static/app/' . ($main['file'] ?? '');
                foreach ($main['css'] ?? [] as $cssFile) {
                    $css .= '<link rel="stylesheet" href="/static/app/' . $cssFile . '">' . "\n";
                }
            }
            $scripts = $css . '<script type="module" src="' . $entry . '"></script>';
        }

        $html = <<<HTML
        <!doctype html>
        <html lang="ru">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Admin — BC</title>
            {$scripts}
        </head>
        <body>
            <div id="root"></div>
        </body>
        </html>
        HTML;

        return new HtmlResponse(200, $html);
    }

    public function adminModules(): Response
    {
        // TODO: collect from registered modules in modules/*/
        $modules = [];

        return new HtmlResponse(
            200,
            json_encode(['modules' => $modules]),
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * Accepts a single file upload, stores it under static/images/{year}/,
     * creates a Media record, generates thumbnails for images, and returns
     * a JSON payload shaped for the admin app to render a <picture>/<video>.
     */
    public function adminMediaUpload(): Response
    {
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
        $slug = trim((string)$slug, '-') ?: 'file';

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
                $width = (int)$info[0];
                $height = (int)$info[1];
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
            foreach ([500, 1000] as $thumbWidth) {
                try {
                    $media->generateThumbnails($thumbWidth);
                } catch (\Throwable $e) {
                    $this->logger->warning("Thumbnail generation failed for width $thumbWidth: {$e->getMessage()}");
                }
            }
        }

        return $this->jsonResponse(200, $this->serializeMedia($media));
    }

    /**
     * Serialize a Media record (with its thumbnails) into the JSON shape
     * consumed by the admin app's MediaBlock and Picture components.
     */
    private function serializeMedia(Media $media): array
    {
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

    private function jsonResponse(int $code, array $data): Response
    {
        return new HtmlResponse(
            $code,
            json_encode($data),
            ['Content-Type' => 'application/json']
        );
    }
}
