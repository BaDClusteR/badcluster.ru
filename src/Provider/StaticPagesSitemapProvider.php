<?php

declare(strict_types=1);

namespace BC\Provider;

use BC\DTO\SitemapEntryDTO;
use BC\Model\StaticPage;
use Runway\Exception\Exception;

/**
 * Feeds published static pages into sitemap.xml.
 *
 * Separate from the core SitemapPagesProvider, which is a fixed list with no
 * database access — same split the Blog module uses for its posts.
 */
class StaticPagesSitemapProvider implements ISitemapPagesProvider {
    /**
     * @inheritDoc
     */
    public function getSitemapPages(): array {
        $result = [];

        try {
            /** @var StaticPage $page */
            foreach (StaticPage::iterate(['published' => true]) as $page) {
                // The home page is already listed as '/' by SitemapPagesProvider.
                if ($page->isHomePage()) {
                    continue;
                }

                // A page carrying robots noindex has no business in the sitemap.
                if (!$page->getIndexable()) {
                    continue;
                }

                $result[] = new SitemapEntryDTO($page->getUrl());
            }
        } catch (Exception) {
        }

        return $result;
    }
}
