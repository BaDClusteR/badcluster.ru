<?php

declare(strict_types=1);

namespace BC\Controller;

use BC\Core\Response\SuccessfulHtmlResponse;
use BC\Widget\Page\Cringe\CringeMuseumPage;
use BC\Widget\Page\Photos\PhotosPage;
use BC\Widget\Page\Screenshots\ScreenshotsPage;
use Runway\Request\Response;
use Throwable;

class About {
    /**
     * @throws Throwable
     */
    public function renderCringeMuseum(): Response {
        return new SuccessfulHtmlResponse(
            new CringeMuseumPage()->render()
        );
    }

    /**
     * @throws Throwable
     */
    public function renderScreenshots(): Response {
        return new SuccessfulHtmlResponse(
            new ScreenshotsPage()->render()
        );
    }

    /**
     * @throws Throwable
     */
    public function renderPhotos(): Response {
        return new SuccessfulHtmlResponse(
            new PhotosPage()->render()
        );
    }
}
