<?php

namespace BC\Modules\Blog\Core\Action\DTO;

readonly class SaveTagRequest {
    public function __construct(
        public int $id,
        public string $name,
        public string $slug
    ) {
    }
}
