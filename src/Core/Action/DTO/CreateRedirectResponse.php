<?php

declare(strict_types=1);

namespace BC\Core\Action\DTO;

use BC\Model\Redirect;

readonly class CreateRedirectResponse {
    public function __construct(
        public Redirect $redirect
    ) {
    }
}