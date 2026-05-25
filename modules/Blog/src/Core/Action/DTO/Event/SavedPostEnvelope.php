<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Core\Action\DTO\Event;

use BC\Modules\Blog\Core\Action\DTO\SavePostRequest;
use BC\Modules\Blog\Model\Post;

readonly class SavedPostEnvelope {
    public function __construct(
        public SavePostRequest $request,
        public Post $post
    ) {
    }
}
