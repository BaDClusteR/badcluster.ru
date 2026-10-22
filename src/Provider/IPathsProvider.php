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

    /**
     * Веб-путь к файлу в /static с версией по времени изменения (?v=…). Для
     * файлов с неизменным от сборки к сборке именем (точки входа module
     * federation): /static отдаётся с годовым immutable-кэшем, и без версии
     * Cloudflare и браузер держат старую сборку.
     *
     * @param string $relativePath путь относительно /static, без ведущего слэша
     */
    public function getVersionedStaticWebPath(string $relativePath): string;

    public function getImagesPath(): string;

    public function getImagesWebPath(): string;
}
