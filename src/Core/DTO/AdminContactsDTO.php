<?php

namespace BC\Core\DTO;

readonly class AdminContactsDTO
{
    public function __construct(
        public string $email,
        public string $telegram,
        public string $steam,
        public string $github
    ) {
    }
}
