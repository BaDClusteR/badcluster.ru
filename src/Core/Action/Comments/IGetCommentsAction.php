<?php

namespace BC\Core\Action\Comments;

use BC\Core\Action\DTO\GetCommentsRequest;
use BC\Core\Action\DTO\GetCommentsResponse;
use Runway\Exception\Exception;

interface IGetCommentsAction {
    /**
     * @throws Exception
     */
    public function run(GetCommentsRequest $request): GetCommentsResponse;
}
