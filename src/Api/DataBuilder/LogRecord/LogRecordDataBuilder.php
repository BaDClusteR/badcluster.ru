<?php

declare(strict_types=1);

namespace BC\Api\DataBuilder\LogRecord;

use BC\Api\DTO\LogRecord\LogRecordDTO;
use BC\Api\DTO\LogRecord\LogRecordRowDTO;
use BC\Core\Converter\IDateConverter;
use BC\Model\LogRecord;

readonly class LogRecordDataBuilder implements ILogRecordDataBuilder {
    public function __construct(
        private IDateConverter $dateConverter
    ) {
    }

    public function buildRow(LogRecord $record): LogRecordRowDTO {
        $lastSeen = $record->getLastSeenAt();

        return new LogRecordRowDTO(
            id: $record->getId(),
            level: $record->getLevel(),
            message: $record->getMessage(),
            date: $this->dateConverter->toShortForm($lastSeen) . ', ' . $lastSeen->format('H:i'),
            occurrences: $record->getOccurrences()
        );
    }

    public function buildEntity(LogRecord $record): LogRecordDTO {
        return new LogRecordDTO(
            id: $record->getId(),
            level: $record->getLevel(),
            message: $record->getMessage(),
            context: $record->getContext(),
            trace: $record->getTrace(),
            url: $record->getUrl(),
            ip: $record->getIp(),
            requestId: $record->getRequestId(),
            occurrences: $record->getOccurrences(),
            firstSeen: $this->dateConverter->toFullForm($record->getFirstSeenAt(), true),
            lastSeen: $this->dateConverter->toFullForm($record->getLastSeenAt(), true)
        );
    }
}
