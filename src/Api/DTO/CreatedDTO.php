<?php

declare(strict_types=1);

namespace BC\Api\DTO;

readonly class CreatedDTO {
    public function __construct(
        public int $id
    ) {
    }
}
