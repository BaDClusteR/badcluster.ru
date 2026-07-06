<?php

namespace BC\Controller;

use BC\Core\Response\HtmlResponse;
use BC\Widget\Page\Page404;
use Runway\Controller\IController404;
use Runway\Request\Response;

class Controller404 implements IController404 {
    public function run(): Response {
        return new HtmlResponse(
            404,
            new Page404()->render()
        );
    }
}
