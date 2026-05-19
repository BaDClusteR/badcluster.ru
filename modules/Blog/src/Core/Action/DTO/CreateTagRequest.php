<?php

namespace BC\Modules\Blog\Core\Action\DTO;

readonly class CreateTagRequest {
    public function __construct(
        public string $name,
        public string $slug
    ) {
    }
}
