<?php

declare(strict_types=1);

namespace BC\Core\Action\DTO;

use BC\Model\Fact;

readonly class CreateFactResponse {
    public function __construct(
        public Fact $fact
    ) {
    }
}