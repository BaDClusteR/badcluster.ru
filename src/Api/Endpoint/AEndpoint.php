<?php

namespace BC\Api\Endpoint;

use ApiPlatform\Exception\RuntimeInternalErrorException;
use Throwable;

abstract class AEndpoint
{
    protected function handleWithException(callable $handler): mixed {
        try {
            return $handler();
        } catch (Throwable $e) {
            throw new RuntimeInternalErrorException($e->getMessage(), $e);
        }
    }
}
