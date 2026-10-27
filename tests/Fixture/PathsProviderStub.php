<?php

declare(strict_types=1);

namespace BC\Tests\Fixture;

use BC\Provider\IPathsProvider;

/**
 * The paths of a test (e.g. temporary folders instead of PROJECT_ROOT/static).
 */
final readonly class PathsProviderStub implements IPathsProvider {
    /**
     * @param string[] $templatePaths
     * @param string[] $widgetPaths
     * @param string[] $assetPaths
     */
    public function __construct(
        private string $staticPath = '/nonexistent/static',
        private array  $templatePaths = [],
        private array  $widgetPaths = [],
        private array  $assetPaths = [],
        private string $staticWebPath = 'http://bc.test/static'
    ) {}

    public function getTemplatePaths(): array {
        return $this->templatePaths;
    }

    public function getWidgetPaths(): array {
        return $this->widgetPaths;
    }

    public function getAssetPaths(): array {
        return $this->assetPaths;
    }

    public function getStaticPath(): string {
        return $this->staticPath;
    }

    public function getStaticWebPath(): string {
        return $this->staticWebPath;
    }

    public function getVersionedStaticWebPath(string $relativePath): string {
        return "$this->staticWebPath/$relativePath";
    }

    public function getImagesPath(): string {
        return "$this->staticPath/images";
    }

    public function getImagesWebPath(): string {
        return "$this->staticWebPath/images";
    }
}
