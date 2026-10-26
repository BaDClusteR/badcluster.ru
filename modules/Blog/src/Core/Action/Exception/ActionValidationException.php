<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Core\Action\Exception;

use Runway\Exception\Exception;
use Throwable;

class ActionValidationException extends Exception {
    /**
     * @param array<string, string> $errors [fieldName => error]
     */
    public function __construct(
        private readonly array $errors,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            'Validation failed',
            0,
            $previous
        );
    }

    /**
     * @return array<string, string> [fieldName => error]
     */
    public function getErrors(): array {
        return $this->errors;
    }
}
