<?php

declare(strict_types=1);

namespace BC\Controller;

use BC\Core\Auth\IAuth;
use BC\Core\Response\SuccessfulHtmlResponse;
use BC\Core\Trait\Controller404Trait;
use BC\Model\StaticPage as StaticPageModel;
use BC\Provider\IStaticPagesProvider;
use BC\Widget\Page\StaticPage\StaticPagePage;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Model\Exception\ModelException;
use Runway\Request\Response;

/**
 * Serves static pages from the site root: /about, /history and so on.
 *
 * These have no path prefix, so the route is a bare `/{slug}` catch-all. Any
 * static route (/blog, /fact) is more specific and wins over it, so this gets
 * only the paths no other route has. When no page matches, it is the regular
 * 404 page.
 */
readonly class StaticPage {
    use Controller404Trait;

    public function __construct(
        private IAuth $auth,
        private IStaticPagesProvider $staticPagesProvider,
    ) {
    }

    /**
     * @throws DBException
     * @throws ModelException
     * @throws QueryBuilderException
     */
    public function render(string $slug): Response {
        // Drafts stay visible to a signed-in admin so they can be previewed in
        // place. The published toggle is the only thing that gates this — the
        // publish date is metadata, not a schedule.
        // The home page is served at '/' by BC\Controller\Index. Declining it
        // here keeps the same content off a second URL.
        if (strtolower($slug) === StaticPageModel::HOME_SLUG) {
            return $this->get404Controller()->run();
        }

        $onlyPublished = !$this->auth->isAuthenticated();
        $page = $this->staticPagesProvider->getBySlug($slug, $onlyPublished);

        if (!$page) {
            return $this->get404Controller()->run();
        }

        // The context goes to the constructor: AWidget::applyContext() runs from
        // there too, and the page requires its model to be present by then.
        return new SuccessfulHtmlResponse(
            new StaticPagePage(['staticPage' => $page])->render()
        );
    }
}
