<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Core\Action\Exception;

use Runway\Exception\Exception;
use Throwable;

class ActionValidationException extends Exception {
    public function __construct(
        private readonly array $errors,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            print_r($errors, true),
            0,
            $previous
        );
    }

    public function getErrors(): array {
        return $this->errors;
    }
}
