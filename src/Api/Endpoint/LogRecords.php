<?php

declare(strict_types=1);

namespace BC\Api\Endpoint;

use ApiPlatform\Attribute as API;
use ApiPlatform\Attribute\Docs;
use ApiPlatform\Exception\BadRequestException;
use BC\Api\DataBuilder\LogRecord\ILogRecordDataBuilder;
use BC\Api\DTO\GetEntitiesListRequest;
use BC\Api\DTO\ListResponseDTO;
use BC\Api\DTO\LogRecord\LogRecordDTO;
use BC\Api\DTO\LogRecord\LogRecordRowDTO;
use BC\Api\DTO\SuccessfulResultDTO;
use BC\Api\Exception\NotFoundException;
use BC\Core\Log\ILogRecorder;
use BC\Model\LogRecord;

/**
 * Журнал предупреждений и ошибок (см. BC\Logger\AlertingLogger): просмотр и
 * удаление разобранных записей
 */
#[Docs\Group('Log records')]
class LogRecords extends AEndpoint {
    public function __construct(
        private readonly ILogRecordDataBuilder $dataBuilder,
        private readonly ILogRecorder $logRecorder
    ) {
    }

    /**
     * @return ListResponseDTO<LogRecordRowDTO>
     *
     * @throws BadRequestException
     */
    #[API\Endpoint(path: 'log-records', method: 'GET')]
    public function getList(
        #[API\Parameter(source: 'query')]
        string $filter = '',
        #[API\Parameter(source: 'query')]
        string $sortBy = '',
        #[API\Parameter(source: 'query')]
        string $sortDir = '',
        #[API\Parameter(source: 'query')]
        int $page = 1,
        #[API\Parameter(source: 'query')]
        int $perPage = self::PER_PAGE_DEFAULT
    ): ListResponseDTO {
        // Пока в журнал ничего не писали, таблицы может и не быть
        $this->logRecorder->ensureSchema();

        return $this->getEntitiesList(
            new GetEntitiesListRequest(
                qb: LogRecord::getQueryBuilder()->orderBy('last_seen_at', 'DESC'),
                filter: $filter,
                columnsToFind: ['message'],
                sortBy: $sortBy,
                sortDir: $sortDir,
                page: $page,
                perPage: $perPage,
                sortableColumns: ['level', 'occurrences']
            ),
            fn (LogRecord $record): LogRecordRowDTO => $this->dataBuilder->buildRow($record)
        );
    }

    /**
     * @throws NotFoundException
     */
    #[API\Endpoint(path: 'log-record', method: 'GET')]
    public function getOne(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id
    ): LogRecordDTO {
        $this->logRecorder->ensureSchema();

        return $this->getEntity(
            LogRecord::class,
            $id,
            'Запись журнала #{{id}} не найдена.',
            fn (LogRecord $record): LogRecordDTO => $this->dataBuilder->buildEntity($record)
        );
    }

    #[API\Endpoint(path: 'log-records', method: 'DELETE')]
    public function delete(
        #[API\Parameter(source: 'body', name: 'rows')]
        array $rows
    ): SuccessfulResultDTO {
        $this->deleteEntities(LogRecord::class, $rows);

        return new SuccessfulResultDTO();
    }
}
