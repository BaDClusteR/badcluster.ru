<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Archiver;

use BC\Modules\Backup\Exception\BackupException;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use ZipArchive;

/**
 * Builds the archive with ext-zip.
 *
 * Entries are stored WITHOUT compression (ZipArchive::CM_STORE): the bulk of /static is
 * already-compressed media (JPEG, MP3, …), so deflating it would burn CPU for no gain.
 */
class ZipArchiveBuilder implements IArchiveBuilder {
    public function build(string $archivePath, string $dbDumpFile, array $staticDirs): void {
        $zip = new ZipArchive();

        $opened = $zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($opened !== true) {
            throw new BackupException('Cannot create archive ' . $archivePath . ' (code ' . $opened . ').');
        }

        $this->addFile($zip, $dbDumpFile, 'database.sql');

        foreach ($staticDirs as $name => $dir) {
            $this->addDirectory($zip, $dir, 'static/' . $name);
        }

        if (!$zip->close()) {
            throw new BackupException('Failed to finalise archive ' . $archivePath . '.');
        }
    }

    private function addFile(ZipArchive $zip, string $path, string $entryName): void {
        if (!$zip->addFile($path, $entryName)) {
            throw new BackupException('Cannot add ' . $path . ' to the archive.');
        }

        $zip->setCompressionName($entryName, ZipArchive::CM_STORE);
    }

    private function addDirectory(ZipArchive $zip, string $dir, string $entryPrefix): void {
        if (!is_dir($dir)) {
            return;
        }

        $zip->addEmptyDir($entryPrefix);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            $relative = substr($item->getPathname(), strlen($dir) + 1);
            $entryName = $entryPrefix . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $relative);

            if ($item->isDir()) {
                $zip->addEmptyDir($entryName);
            } elseif ($item->isFile()) {
                $this->addFile($zip, $item->getPathname(), $entryName);
            }
        }
    }
}