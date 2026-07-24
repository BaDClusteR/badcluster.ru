<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Dumper;

use BC\Modules\Backup\Config\DatabaseConfig;
use BC\Modules\Backup\Exception\BackupException;

interface IDatabaseDumper {
    /**
     * Dumps the database (honouring the include/exclude rules) to $outputFile as SQL.
     *
     * @throws BackupException
     */
    public function dump(DatabaseConfig $config, string $outputFile): void;
}