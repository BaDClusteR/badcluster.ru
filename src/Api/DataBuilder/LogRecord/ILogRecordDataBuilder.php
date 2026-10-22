<?php

declare(strict_types=1);

namespace BC\Api\DataBuilder\LogRecord;

use BC\Api\DTO\LogRecord\LogRecordDTO;
use BC\Api\DTO\LogRecord\LogRecordRowDTO;
use BC\Model\LogRecord;

interface ILogRecordDataBuilder {
    public function buildRow(LogRecord $record): LogRecordRowDTO;

    public function buildEntity(LogRecord $record): LogRecordDTO;
}
