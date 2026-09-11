<?php

declare(strict_types=1);

namespace BC\Controller;

use BC\Core\Auth\IAuth;
use BC\Core\Response\HtmlResponse;
use BC\Model\StaticPage;
use BC\Provider\IRandomFactProvider;
use BC\Provider\IStaticPagesProvider;
use BC\Widget\Common\Footer\Fact;
use BC\Widget\Page\Home\HomePage;
use BC\Widget\Page\StaticPage\StaticPagePage;
use Runway\Exception\Exception;
use Runway\Request\Response;
use Runway\Singleton\Container;
use Throwable;

readonly class Index {
    public function __construct(
        private IAuth $auth,
        private IStaticPagesProvider $staticPagesProvider,
    ) {
    }

    /**
     * A static page with the reserved `home` slug takes over the site root, so
     * the front page can be edited in the admin. Without one, the hardcoded
     * HomePage is used, exactly as before.
     *
     * @throws Throwable
     */
    public function run(): Response {
        $page = $this->getHomeStaticPage();

        return new HtmlResponse(
            200,
            $page
                ? new StaticPagePage(['staticPage' => $page, 'isHomePage' => true])->render()
                : new HomePage()->render()
        );
    }

    private function getHomeStaticPage(): ?StaticPage {
        try {
            return $this->staticPagesProvider->getBySlug(
                StaticPage::HOME_SLUG,
                !$this->auth->isAuthenticated()
            );
        } catch (Exception) {
            // A broken query should not take the front page down with it.
            return null;
        }
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
