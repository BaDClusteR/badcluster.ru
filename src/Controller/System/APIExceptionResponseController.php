<?php

declare(strict_types=1);

namespace BC\Controller\System;

use BC\Exception\UnprocessableEntityException;
use BC\Logger\AlertingLogger;
use Throwable;

class APIExceptionResponseController extends \ApiPlatform\Controller\System\ApiExceptionResponseController {
    protected function getExceptionJsonData(Throwable $exception, bool $isForLogs = false): array {
        $result = $exception instanceof UnprocessableEntityException
            ? [
                'message' => $exception->getError(),
                'errors'  => $exception->getFieldErrors(),
            ]
            : parent::getExceptionJsonData($exception, $isForLogs);

        // По коду AlertingLogger отличает ошибки клиента (4xx) от ошибок сайта:
        // первые остаются только в файле, в журнал админки и Slack не идут
        if ($isForLogs) {
            $result[AlertingLogger::CONTEXT_HTTP_CODE] = $this->getErrorCode($exception);
        }

        return $result;
    }

    protected function isAPIException(Throwable $exception): bool {
        return parent::isAPIException($exception)
               || $exception instanceof UnprocessableEntityException;
    }
}
