<?php

declare(strict_types=1);

namespace BC\Controller;

use BC\Core\Auth\IAuth;
use BC\Core\Response\SuccessfulHtmlResponse;
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
 * These have no path prefix, so the route is a bare `/{slug}` catch-all. It is
 * registered with a high `priority`, which puts it last in the router's list, so
 * every hardcoded route gets first refusal. When no page matches, this returns
 * null rather than a 404 — Runway's router only stops at a `Response`, so a null
 * hands the request back and the chain ends at the regular 404 controller.
 */
readonly class StaticPage {
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
    public function render(string $slug): ?Response {
        // Drafts stay visible to a signed-in admin so they can be previewed in
        // place. The published toggle is the only thing that gates this — the
        // publish date is metadata, not a schedule.
        // The home page is served at '/' by BC\Controller\Index. Declining it
        // here keeps the same content off a second URL.
        if (strtolower($slug) === StaticPageModel::HOME_SLUG) {
            return null;
        }

        $onlyPublished = !$this->auth->isAuthenticated();
        $page = $this->staticPagesProvider->getBySlug($slug, $onlyPublished);

        if (!$page) {
            return null;
        }

        // The context goes to the constructor: AWidget::applyContext() runs from
        // there too, and the page requires its model to be present by then.
        return new SuccessfulHtmlResponse(
            new StaticPagePage(['staticPage' => $page])->render()
        );
    }
}
