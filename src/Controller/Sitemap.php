<?php

declare(strict_types=1);

namespace BC\Controller;

use BC\DTO\SitemapEntryDTO;
use BC\Provider\ISitemapPagesProvider;
use Runway\Request\Response;
use Runway\Singleton\Container;

class Sitemap {
    public function renderSitemap(): Response {
        return new Response(
            200,
            new \BC\Widget\Sitemap()->render(['pages' => $this->getPages()]),
            ['Content-Type' => 'text/xml; charset=utf-8']
        );
    }

    /**
     * @return SitemapEntryDTO[]
     */
    private function getPages(): array {
        return array_merge(
            ...array_map(
                static fn (ISitemapPagesProvider $provider): array => $provider->getSitemapPages(),
                $this->getSitemapPagesProviders()
            )
        );
    }

    /**
     * @return ISitemapPagesProvider[]
     */
    private function getSitemapPagesProviders(): array {
        return Container::getInstance()->getServicesByTag('sitemap');
    }
}
