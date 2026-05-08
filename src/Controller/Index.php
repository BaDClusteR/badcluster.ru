<?php

namespace BC\Controller;

use BC\Core\Response\HtmlResponse;
use BC\Model\Media;
use BC\Provider\IPathsProvider;
use BC\Widget\Page\Home;
use Runway\Request\Response;
use Runway\Singleton\Container;

class Index extends AController
{
    public function test(): Response
    {
        return new HtmlResponse(
            200,
            new Home()->render()
        );
    }

    public function adminModules(): Response
    {
        // TODO: collect from registered modules in modules/*/
        $modules = [];

        return new HtmlResponse(
            200,
            json_encode(['modules' => $modules]),
            ['Content-Type' => 'application/json']
        );
    }
}
