<?php

declare(strict_types=1);

namespace BC\Provider;

interface IPathsProvider {
    /**
     * @return string[]
     */
    public function getTemplatePaths(): array;

    /**
     * @return string[]
     */
    public function getWidgetPaths(): array;

    /**
     * @return string[]
     */
    public function getAssetPaths(): array;

    public function getStaticPath(): string;

    public function getStaticWebPath(): string;

    public function getImagesPath(): string;

    public function getImagesWebPath(): string;
}
