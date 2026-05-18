<?php

namespace BC\Exception;

use Runway\Exception\Exception;
use Throwable;

class UnprocessableEntityException extends Exception {
    public function __construct(
        private array $fieldErrors,
        private ?string $error = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($error, 422, $previous);
    }

    public function getError(): ?string {
        return $this->error;
    }

    /**
     * @return array<string, string> [fieldName => error]
     */
    public function getFieldErrors(): array {
        return $this->fieldErrors;
    }
}
