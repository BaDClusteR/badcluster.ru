<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Command;

use BC\Modules\Backup\Service\IBackupService;
use Runway\Console\Command\ACommand;
use Runway\Console\Input\IInput;
use Runway\Console\Output\IOutput;

/**
 * Entry point for scheduled backups. Intended to be run daily from OS cron, e.g.:
 *
 *   0 4 * * *  cd /path/to/site && ./console backup:run >> log/backup.log 2>&1
 */
class BackupRunCommand extends ACommand {
    public function __construct(
        private readonly IBackupService $backupService,
    ) {
        parent::__construct();
    }

    public function getName(): string {
        return 'backup:run';
    }

    public function getDescription(): string {
        return 'Create a backup (DB + /static), upload it to configured destinations and log the result';
    }

    protected function execute(IInput $input, IOutput $output): int {
        $output->info('Starting backup…');

        $result = $this->backupService->run();

        $output->writeln('Archive:      ' . $result->archiveName);
        $output->writeln('Size:         ' . $this->formatBytes($result->sizeBytes));
        if ($result->succeededDestinations !== []) {
            $output->writeln('Uploaded to:  ' . implode(', ', $result->succeededDestinations));
        }
        if ($result->url !== null) {
            $output->writeln('URL:          ' . $result->url);
        }

        foreach ($result->errors as $error) {
            $output->warning($error);
        }

        if ($result->success) {
            $output->success('Backup completed.');

            return 0;
        }

        $output->error('Backup failed.');

        return 1;
    }

    private function formatBytes(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $value = (float)$bytes;
        $unit = 0;

        while ($value >= 1024 && $unit < count($units) - 1) {
            $value /= 1024;
            $unit++;
        }

        return sprintf('%.2f %s', $value, $units[$unit]);
    }
}
