<?php

declare(strict_types=1);

namespace BC\Controller;

use BC\Core\Response\HtmlResponse;
use BC\Provider\IRandomFactProvider;
use BC\Widget\Common\Footer\Fact;
use BC\Widget\Page\Home\HomePage;
use BC\Widget\Page404;
use Runway\Request\Response;
use Runway\Singleton\Container;

readonly class Index {
    public function run(): Response {
        return new HtmlResponse(
            200,
            new HomePage()->render()
        );
    }

    public function renderRandomFact(): Response {
        return new Response(
            200,
            new Fact()->render([
                'fact' => Container::getInstance()->getService(IRandomFactProvider::class)->getRandomFact()
            ]),
            [
                'Content-Type' => 'text/plain',
                'X-Robots-Tag' => 'none'
            ]
        );
    }
}
