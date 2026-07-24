<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Archiver;

use BC\Modules\Backup\Exception\BackupException;

interface IArchiveBuilder {
    /**
     * Packs the DB dump and the given static folders into a single archive at $archivePath.
     *
     * The dump lands at "database.sql" inside the archive; each static folder lands under
     * "static/<name>/…".
     *
     * @param string                $archivePath absolute path of the archive to create
     * @param string                $dbDumpFile  absolute path to the SQL dump to embed
     * @param array<string, string> $staticDirs  map of folder name => absolute directory path
     *
     * @throws BackupException
     */
    public function build(string $archivePath, string $dbDumpFile, array $staticDirs): void;
}