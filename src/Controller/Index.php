<?php

namespace BC\Controller;

use BC\Core\Response\HtmlResponse;
use BC\Widget\Page\Home;
use Runway\Request\Response;

class Index extends AController
{
    public function test(): Response
    {
        return new HtmlResponse(
            200,
            new Home()->render()
        );
    }
}
