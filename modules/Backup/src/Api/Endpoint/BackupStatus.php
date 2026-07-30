<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Api\Endpoint;

use ApiPlatform\Attribute as API;
use ApiPlatform\Attribute\Docs;
use BC\Api\Endpoint\AEndpoint;
use BC\Modules\Backup\Api\DTO\BackupStatusDTO;
use BC\Modules\Backup\Model\BackupLog;
use BC\Modules\Backup\Repository\IBackupLogRepository;

#[Docs\Group('Backups')]
class BackupStatus extends AEndpoint {
    public function __construct(
        private readonly IBackupLogRepository $backupLogRepository
    ) {
    }

    #[API\Endpoint(path: 'backup_status', method: 'GET')]
    public function get(): BackupStatusDTO {
        $log = $this->handleWithException(function (): ?BackupLog {
            // На свежей установке таблицы лога ещё нет — она создаётся
            // при первом бэкапе; ensureSchema() безопасно вызывать повторно
            $this->backupLogRepository->ensureSchema();

            return BackupLog::findOne([], ['createdAt', 'DESC']);
        });

        return new BackupStatusDTO(
            lastBackupAt: $log?->getCreatedAt()->getTimestamp(),
            success: $log?->getSuccess() ?? false,
            sizeBytes: $log?->getSizeBytes() ?? 0,
            archiveName: $log?->getArchiveName() ?? '',
            url: $log?->getUrl() ?? '',
            error: $log?->getError() ?? ''
        );
    }
}
