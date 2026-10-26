<?php

declare(strict_types=1);

namespace BC\Tests\Support;

use FilesystemIterator;
use LogicException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

/**
 * The temporary folders of the tests. remove() deletes only a folder inside the temporary folder of the system that
 * does not look like a project: a test (or a mutant) that passes a wrong path cannot delete the site.
 */
final class TempDir {
    /**
     * A new empty folder; the path has forward slashes (they work on Windows too).
     */
    public static function create(string $prefix = 'bc-test-'): string {
        $path = str_replace('\\', '/', sys_get_temp_dir()) . '/' . $prefix . bin2hex(random_bytes(6));

        if (!mkdir($path, 0700, true)) {
            throw new RuntimeException("Cannot create $path.");
        }

        return $path;
    }

    /**
     * @throws LogicException The path is not in the temporary folder of the system, or it looks like a project.
     */
    public static function remove(string $path): void {
        if (!file_exists($path) && !is_link($path)) {
            return;
        }

        self::assertTemporary($path);

        if (is_link($path) || is_file($path)) {
            unlink($path);

            return;
        }

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        /** @var SplFileInfo $item */
        foreach ($items as $item) {
            if ($item->isDir() && !$item->isLink()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($path);
    }

    /**
     * Inside the temporary folder of the system (not the folder itself), not in the project, and not a project or a
     * repository itself.
     */
    public static function isTemporary(string $path): bool {
        $temp = realpath(sys_get_temp_dir());
        $real = realpath(is_link($path) ? dirname($path) : $path);
        $project = realpath(PROJECT_ROOT);

        if ($temp === false || $real === false) {
            return false;
        }

        $temp = rtrim(str_replace('\\', '/', $temp), '/') . '/';
        $real = str_replace('\\', '/', $real);

        return str_starts_with($real . '/', $temp)
            && $real . '/' !== $temp
            && ($project === false || !str_starts_with($real . '/', str_replace('\\', '/', $project) . '/'))
            && !file_exists("$real/.git")
            && !file_exists("$real/composer.json")
            && !file_exists("$real/vendor/autoload.php");
    }

    /**
     * @throws LogicException
     */
    public static function assertTemporary(string $path): void {
        if (!self::isTemporary($path)) {
            throw new LogicException("Refusing to delete $path: it is not a temporary folder of the tests.");
        }
    }
}
