<?php

namespace BC\Modules\Blog\Core\Action\DTO;

readonly class ValidatorResponse
{
    /**
     * @param bool  $successful
     * @param array<string, string> $errors
     */
    public function __construct(
        public bool $successful = true,
        public array $errors = []
    ) {
    }
}
