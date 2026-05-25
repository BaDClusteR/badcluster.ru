<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Core\Action\Post;

use BC\Modules\Blog\Core\Action\DTO\SavePostRequest;
use Runway\Exception\Exception;

interface ISavePostAction {
    /**
     * @throws Exception
     */
    public function run(SavePostRequest $request): void;
}
