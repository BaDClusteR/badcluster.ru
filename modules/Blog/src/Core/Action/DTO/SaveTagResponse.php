<?php

namespace BC\Modules\Blog\Core\Action\DTO;

use BC\Modules\Blog\Model\Tag;

readonly class SaveTagResponse {
    public function __construct(
        public Tag $tag
    ) {
    }
}
