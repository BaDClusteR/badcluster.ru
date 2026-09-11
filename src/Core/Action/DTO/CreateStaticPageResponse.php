<?php

declare(strict_types=1);

namespace BC\Core\Action\DTO;

use BC\Model\StaticPage;

readonly class CreateStaticPageResponse {
    public function __construct(
        public StaticPage $staticPage
    ) {
    }
}
