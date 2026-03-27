<?php

namespace BC\Core\Asset;

use BC\Core\Asset\DTO\BundleFileDTO;
use BC\Core\Asset\Minifier\IMinifier;
use BC\Core\Provider\IPathsProvider;
use BC\Core\Scanner\IWidgetClassScanner;
use BC\Model\Config;
use BC\Widget\IAssetProvider;
use ReflectionClass;
use ReflectionException;
use Runway\Exception\Exception;
use Runway\FileSystem\Exception\CannotCreateDirectoryException;
use Runway\FileSystem\Exception\CannotDeleteFileException;
use Runway\FileSystem\Exception\FileSystemException;
use Runway\FileSystem\IFileSystem;
use Runway\Logger\ILogger;
use Runway\Singleton\Container;

class AssetBuilder implements IAssetBuilder
{
    private const string CONFIG_NAME = 'asset_bundles';

    /** @var array<string, array<string, BundleFileDTO[]>> bundle => type => files */
    private array $bundles = [];

    public function __construct(
        private readonly IPathsProvider $pathsProvider,
        private readonly IFileSystem $fileSystem,
        private readonly IWidgetClassScanner $scanner,
        private readonly IMinifier $minifier,
        private readonly ILogger $logger
    ) {
    }

    public function addFile(string $bundleName, string $relativePath, int $priority = 100): void
    {
        $type = pathinfo($relativePath, PATHINFO_EXTENSION);
        $absolutePath = $this->resolveAssetPath($relativePath);

        if ($absolutePath === null) {
            return;
        }

        foreach ($this->bundles[$bundleName][$type] ?? [] as &$bundle) {
            if ($bundle->relativePath === $relativePath) {
                $bundle = new BundleFileDTO($relativePath, $absolutePath, $priority);
                return;
            }
        }
        unset($bundle);

        $this->bundles[$bundleName][$type][] = new BundleFileDTO($relativePath, $absolutePath, $priority);
    }

    /**
     * Collects assets from all IAssetProvider widgets and from tagged services,
     * then builds and writes the minified bundle files.
     *
     * @throws Exception
     */
    public function buildBundles(): void {
        $this->collectAssets();
        $storedBundles = $this->loadStoredBundles();
        $staticDir = $this->pathsProvider->getStaticPath();

        if (!is_dir($staticDir)) {
            try {
                $this->fileSystem->mkdir($staticDir);
            } catch (CannotCreateDirectoryException $e) {
                throw new Exception($e->getMessage(), $e->getCode(), $e);
            }
        }

        foreach ($this->bundles as $bundleName => $types) {
            foreach ($types as $type => $files) {
                usort(
                    $files,
                    static fn(BundleFileDTO $a, BundleFileDTO $b) => $a->priority <=> $b->priority
                );

                $combined = '';
                foreach ($files as $file) {
                    $combined .= file_get_contents($file->absolutePath) . "\n";
                }

                $minified = $this->minifier->minify($combined, $type);
                $hash = md5($minified);
                $key = "$bundleName.$type";

                $version = 1;
                if (isset($storedBundles[$key])) {
                    if ($storedBundles[$key]['hash'] === $hash) {
                        $version = $storedBundles[$key]['version'];
                    } else {
                        $version = $storedBundles[$key]['version'] + 1;
                        $this->removeOldBundleFile($storedBundles[$key]['path']);
                    }
                }

                $typeDir = "$staticDir/$type";

                if (!is_dir($typeDir)) {
                    try {
                        $this->fileSystem->mkdir($typeDir);
                    } catch (CannotCreateDirectoryException $e) {
                        throw new Exception($e->getMessage(), $e->getCode(), $e);
                    }
                }

                $fileName = "$bundleName.v$version.min.$type";
                file_put_contents("$typeDir/$fileName", $minified);

                $storedBundles[$key] = [
                    'path' => "$type/$fileName",
                    'hash' => $hash,
                    'version' => $version,
                ];
            }
        }

        $this->saveStoredBundles($storedBundles);
    }

    protected function collectAssets(): void {
        $this->collectAssetsFromProviders();
        $this->collectAssetsFromTags();
    }

    /**
     * @throws Exception
     */
    public function buildAssets(): void
    {
        $this->buildBundles();
        $this->copyStaticAssets();
    }

    public function getBundleFileName(string $bundleName, string $type): ?string {
        $storedBundles = $this->loadStoredBundles();
        $key = "$bundleName.$type";

        return $storedBundles[$key]['path'] ?? null;
    }

    public function getBundleWebPath(string $bundleName, string $type): ?string {
        return $this->pathsProvider->getStaticWebPath() . "/{$this->getBundleFileName($bundleName, $type)}";
    }

    /**
     * Scans widget directories for classes implementing IAssetProvider
     * and calls their static getAssets() method.
     */
    private function collectAssetsFromProviders(): void
    {
        /** @var class-string<IAssetProvider> $className */
        foreach ($this->scanner->getWidgetClasses() as $className) {
            try {
                $reflection = new ReflectionClass($className);
            } catch (ReflectionException $e) {
                $this->logger->warning(
                    sprintf('Cannot create Reflection for %s: %s', $className, $e->getMessage()),
                );

                continue;
            }

            if (!$reflection->implementsInterface(IAssetProvider::class)) {
                continue;
            }

            foreach ($className::getAssets() as $asset) {
                $this->addFile(
                    $asset->bundle,
                    $asset->path,
                    $asset->priority
                );
            }
        }
    }

    /**
     * Collects assets from services tagged with "asset-bundle".
     * Tag extra fields: bundle, path, priority.
     */
    private function collectAssetsFromTags(): void
    {
        $container = Container::getInstance();

        foreach ($container->getServiceTagsByName('asset-bundle') as $tagInfo) {
            $extra = $tagInfo['extra'];

            if (!empty($extra['bundle']) && !empty($extra['path'])) {
                $this->addFile(
                    (string)$extra['bundle'],
                    (string)$extra['path'],
                    (int)($extra['priority'] ?? 100),
                );
            }
        }
    }

    /**
     * @throws Exception
     */
    private function copyStaticAssets(): void
    {
        $staticDir = $this->pathsProvider->getStaticPath();
        $excludeDirs = ['js', 'css'];

        foreach ($this->pathsProvider->getAssetPaths() as $assetPath) {
            if (!is_dir($assetPath)) {
                continue;
            }

            $entries = scandir($assetPath);

            if ($entries === false) {
                continue;
            }

            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                if (!is_dir("$assetPath/$entry")) {
                    continue;
                }

                if (in_array($entry, $excludeDirs, true)) {
                    continue;
                }

                $this->copyDirectory("$assetPath/$entry", "$staticDir/$entry");
            }
        }
    }

    /**
     * @throws Exception
     */
    private function copyDirectory(string $src, string $dst): void
    {
        if (!is_dir($dst)) {
            try {
                $this->fileSystem->mkdir($dst);
            } catch (CannotCreateDirectoryException $e) {
                throw new Exception($e->getMessage(), $e->getCode(), $e);
            }
        }

        $entries = scandir($src);

        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $srcPath = "$src/$entry";
            $dstPath = "$dst/$entry";

            if (is_dir($srcPath)) {
                $this->copyDirectory($srcPath, $dstPath);
            } else {
                try {
                    $this->fileSystem->copy($srcPath, $dstPath, isOverwrite: true);
                } catch (FileSystemException $e) {
                    $this->logger->warning("Failed to copy $srcPath to $dstPath: " . $e->getMessage());
                }
            }
        }
    }

    private function resolveAssetPath(string $relativePath): ?string
    {
        foreach ($this->pathsProvider->getAssetPaths() as $path) {
            $fullPath = "$path/$relativePath";

            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }

        return null;
    }

    /**
     * @return array{path: string, hash: string, version: integer}[]
     */
    private function loadStoredBundles(): array
    {
        try {
            $config = Config::findOne(['name' => self::CONFIG_NAME]);

            if ($config === null || $config->getValue() === null) {
                return [];
            }

            return json_decode($config->getValue(), true, 512, JSON_THROW_ON_ERROR) ?: [];
        } catch (\Exception $e) {
            $this->logger->warning(
                'Error while loading stored bundles: ' . $e->getMessage()
            );

            return [];
        }
    }

    /**
     * @param array{path: string, hash: string, version: integer}[] $bundles
     *
     * @throws Exception
     */
    private function saveStoredBundles(array $bundles): void
    {
        try {
            $config = Config::findOne(['name' => self::CONFIG_NAME]);

            if ($config === null) {
                $config = new Config();
                $config->setName(self::CONFIG_NAME);
            }

            $config->setValue(json_encode($bundles, JSON_THROW_ON_ERROR));
            $config->persist();
        } catch (\Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    private function removeOldBundleFile(string $relativePath): void
    {
        $fullPath = PROJECT_ROOT . '/static/' . $relativePath;

        if (file_exists($fullPath)) {
            try {
                $this->fileSystem->remove($fullPath);
            } catch (CannotDeleteFileException) {}
        }
    }
}
