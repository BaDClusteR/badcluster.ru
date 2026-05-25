<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Core\Action\DTO;

readonly class GetPostRequest {
    public function __construct(
        public int $id
    ) {
    }
}
