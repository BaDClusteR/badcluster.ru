<?php

namespace BC\Controller;

use BC\Core\Response\HtmlResponse;
use BC\Model\Media;
use BC\Provider\IPathsProvider;
use BC\Widget\Page\Home;
use Runway\Request\Response;
use Runway\Singleton\Container;

readonly class Index
{
    public function run(): Response
    {
        return new HtmlResponse(
            200,
            new Home()->render()
        );
    }
}
