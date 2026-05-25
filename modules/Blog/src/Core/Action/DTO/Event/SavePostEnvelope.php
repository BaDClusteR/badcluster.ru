<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Core\Action\DTO\Event;

use BC\Modules\Blog\Core\Action\DTO\SavePostRequest;

readonly class SavePostEnvelope {
    public function __construct(
        public SavePostRequest $request
    ) {
    }
}
