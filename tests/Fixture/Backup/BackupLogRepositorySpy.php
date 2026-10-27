<?php

declare(strict_types=1);

namespace BC\Tests\Fixture\Backup;

use BC\Modules\Backup\Model\BackupLog;
use BC\Modules\Backup\Repository\IBackupLogRepository;
use Throwable;

/**
 * Collects the saved records instead of persisting them (or throws the given exception).
 */
final class BackupLogRepositorySpy implements IBackupLogRepository {
    /** @var list<BackupLog> */
    public array $saved = [];

    public int $ensureSchemaCalls = 0;

    public function __construct(
        private readonly ?Throwable $exception = null
    ) {}

    public function ensureSchema(): void {
        $this->ensureSchemaCalls++;
    }

    public function save(BackupLog $log): void {
        if ($this->exception !== null) {
            throw $this->exception;
        }

        $this->saved[] = $log;
    }
}
