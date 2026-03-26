<?php

declare(strict_types=1);

namespace BC\Controller;

use Runway\Logger\ILogger;
use Runway\Request\IRequest;
use Runway\Request\Response;

abstract class AController
{
    public function __construct(
        protected IRequest $request,
        protected ILogger  $logger
    ) {}

    public function test(): Response
    {
        return new Response(
            200,
            'Test'
        );
    }
}