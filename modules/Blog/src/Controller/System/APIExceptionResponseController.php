<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Controller\System;

use BC\Exception\UnprocessableEntityException;
use Throwable;

class APIExceptionResponseController extends \ApiPlatform\Controller\System\APIExceptionResponseController {
    protected function getExceptionJsonData(Throwable $exception, bool $isForLogs = false): array {
        if ($exception instanceof UnprocessableEntityException) {
            return [
                'message' => $exception->getError(),
                'errors'  => $exception->getFieldErrors()
            ];
        }

        return parent::getExceptionJsonData($exception, $isForLogs);
    }

    protected function isAPIException(Throwable $exception): bool {
        return parent::isAPIException($exception)
               || $exception instanceof UnprocessableEntityException;
    }
}
