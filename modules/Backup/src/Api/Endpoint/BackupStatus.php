<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Api\Endpoint;

use ApiPlatform\Attribute as API;
use ApiPlatform\Attribute\Docs;
use BC\Api\Endpoint\AEndpoint;
use BC\Core\Helper\MixedValue;
use BC\Modules\Backup\Api\DTO\BackupDestinationStatusDTO;
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
            error: $log?->getError() ?? '',
            destinations: $log ? $this->getDestinations($log) : []
        );
    }

    /**
     * @return BackupDestinationStatusDTO[]
     */
    private function getDestinations(BackupLog $log): array {
        $results = json_decode($log->getDestinationResults(), true);

        // Записи до появления destination_results знают только, куда бэкап дошёл
        if (!is_array($results)) {
            return array_map(
                static fn (string $name): BackupDestinationStatusDTO => new BackupDestinationStatusDTO($name, true, ''),
                array_values(array_filter(array_map('trim', explode(',', $log->getDestinations()))))
            );
        }

        return array_map(
            static fn (array $result): BackupDestinationStatusDTO => new BackupDestinationStatusDTO(
                name: MixedValue::toString($result['name'] ?? ''),
                success: (bool) ($result['success'] ?? false),
                error: MixedValue::toString($result['error'] ?? '')
            ),
            array_values(array_filter($results, 'is_array'))
        );
    }
}
