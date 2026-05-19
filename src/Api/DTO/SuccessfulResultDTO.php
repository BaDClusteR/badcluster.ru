<?php

namespace BC\Api\DTO;

readonly class SuccessfulResultDTO {
    public function __construct(
        public string $status = 'success'
    ) {
    }
}
