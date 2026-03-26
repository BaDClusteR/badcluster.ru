<?php

namespace BC\Core\Scanner;

use BC\Core\Provider\IPathsProvider;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

readonly class WidgetClassScanner implements IWidgetClassScanner
{
    public function __construct(
        private IPathsProvider $pathsProvider,
    ) {
    }

    public function getWidgetClasses(): array
    {
        $classes = [];

        foreach ($this->pathsProvider->getWidgetPaths() as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $fqcn = $this->extractClassName($file->getPathname());

                if ($fqcn !== null && class_exists($fqcn)) {
                    $classes[] = $fqcn;
                }
            }
        }

        return $classes;
    }

    private function extractClassName(string $filePath): ?string
    {
        $content = file_get_contents($filePath);

        $namespace = '';
        if (preg_match('/^\s*namespace\s+([^;{]+)/m', $content, $m)) {
            $namespace = trim($m[1]);
        }

        if (!preg_match('/^\s*(?:abstract\s+|final\s+|readonly\s+)*class\s+(\w+)/m', $content, $m)) {
            return null;
        }

        $class = $m[1];

        return $namespace !== '' ? "$namespace\\$class" : $class;
    }
}
