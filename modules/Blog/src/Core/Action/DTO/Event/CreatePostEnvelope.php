<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Core\Action\DTO\Event;

use BC\Modules\Blog\Core\Action\DTO\CreatePostRequest;

readonly class CreatePostEnvelope {
    public function __construct(
        public CreatePostRequest $request
    ) {
    }
}
