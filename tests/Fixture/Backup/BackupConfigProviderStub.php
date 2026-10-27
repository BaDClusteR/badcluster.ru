<?php

declare(strict_types=1);

namespace BC\Tests\Fixture\Backup;

use BC\Modules\Backup\Config\BackupConfig;
use BC\Modules\Backup\Config\IBackupConfigProvider;

final readonly class BackupConfigProviderStub implements IBackupConfigProvider {
    public function __construct(
        private BackupConfig $config
    ) {}

    public function getConfig(): BackupConfig {
        return $this->config;
    }
}
