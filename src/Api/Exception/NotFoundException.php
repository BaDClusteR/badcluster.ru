<?php

namespace BC\Api\Exception;

use ApiPlatform\Exception\ApiException;
use Throwable;

class NotFoundException extends ApiException
{
    public function __construct(
        array|string $errors,
        ?Throwable   $previous = null
    ) {
        parent::__construct((array)$errors, 404, $previous);
    }
}
