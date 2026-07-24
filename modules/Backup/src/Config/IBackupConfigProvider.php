<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Config;

interface IBackupConfigProvider {
    public function getConfig(): BackupConfig;
}