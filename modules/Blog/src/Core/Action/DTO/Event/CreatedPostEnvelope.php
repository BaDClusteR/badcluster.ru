<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Core\Action\DTO\Event;

use BC\Modules\Blog\Core\Action\DTO\CreatePostRequest;
use BC\Modules\Blog\Core\Action\DTO\CreatePostResponse;

readonly class CreatedPostEnvelope {
    public function __construct(
        public CreatePostRequest $request,
        public CreatePostResponse $response
    ) {
    }
}
