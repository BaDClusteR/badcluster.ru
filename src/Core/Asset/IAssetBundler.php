<?php

namespace BC\Core\Asset;

interface IAssetBundler
{
    public function addFile(string $bundleName, string $relativePath, int $priority = 100): void;

    public function buildBundles(): void;

    public function getBundleFileName(string $bundleName, string $type): ?string;

    public function getBundleWebPath(string $bundleName, string $type): ?string;
}
