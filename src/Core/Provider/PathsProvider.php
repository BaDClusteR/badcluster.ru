<?php

declare(strict_types=1);

namespace BC\Core\Provider;

use BC\Core\Config\IWebsiteSettings;
use Runway\Module\DTO\ModuleDTO;
use Runway\Module\Provider\IModuleProvider;

class PathsProvider implements IPathsProvider
{
    public function __construct(
        protected IModuleProvider $moduleProvider,
        protected IWebsiteSettings $websiteSettings
    ) {
    }

    /**
     * @return string[]
     */
    public function getTemplatePaths(): array {
        return $this->getSubpaths('/templates');
    }

    public function getWidgetPaths(): array {
        return $this->getSubpaths('/src/Widget');
    }

    public function getAssetPaths(): array {
        return $this->getSubpaths('/assets');
    }

    /**
     * @param string $subpath
     *
     * @return string[]
     */
    protected function getSubpaths(string $subpath): array {
        return array_values(
            array_filter(
                [
                    PROJECT_ROOT . $subpath,
                    ...array_map(
                        static function(ModuleDTO $module) use ($subpath): string {
                            $dir = $module->rootPath . $subpath;

                            if (is_dir($dir)) {
                                return $dir;
                            }

                            return '';
                        },
                        $this->moduleProvider->getEnabledModules()
                    )
                ]
            )
        );
    }

    public function getStaticPath(): string {
        return PROJECT_ROOT . '/static';
    }

    public function getStaticWebPath(): string
    {
        return $this->websiteSettings->getWebRoot() . '/static';
    }
}
