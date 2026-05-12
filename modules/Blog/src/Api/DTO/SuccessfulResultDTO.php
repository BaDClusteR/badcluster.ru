<?php

namespace BC\Modules\Blog\Api\DTO;

readonly class SuccessfulResultDTO
{
    public function __construct(
        public string $status = 'success'
    ) {
    }
}
