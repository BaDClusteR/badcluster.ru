<?php

namespace BC\Controller;

use BC\Core\Response\SuccessfulHtmlResponse;
use BC\Widget\Page\About\AboutMePage;
use BC\Widget\Page\Cringe\CringeMuseumPage;
use BC\Widget\Page\History\HistoryPage;
use Runway\Request\Response;

class About {
    public function renderAboutMe(): Response {
        return new SuccessfulHtmlResponse(
            new AboutMePage()->render()
        );
    }

    public function renderHistory(): Response {
        return new SuccessfulHtmlResponse(
            new HistoryPage()->render()
        );
    }

    public function renderCringeMuseum(): Response {
        return new SuccessfulHtmlResponse(
            new CringeMuseumPage()->render()
        );
    }
}
