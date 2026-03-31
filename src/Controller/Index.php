<?php

namespace BC\Controller;

use BC\Core\Response\HtmlResponse;
use BC\Widget\Page\Home;
use Runway\Request\Response;

class Index extends AController
{
    public function test(): Response
    {
        $image = \BC\Model\Media::findOne(['id' => 68]);
        $gen = \Runway\Singleton\Container::getInstance()->getService(\BC\Generator\IThumbnailsGenerator::class);
        $gen->generateThumbnails($image, [500]);
//        $image->remove();
        die();
        return new HtmlResponse(
            200,
            new Home()->render()
        );
    }
}
