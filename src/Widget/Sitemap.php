<?php

declare(strict_types=1);

namespace BC\Widget;

use BC\Core\Trait\WebsiteSettingsTrait;
use BC\DTO\SitemapEntryDTO;

class Sitemap extends AWidget {
    use WebsiteSettingsTrait;

    private string $webRoot;

    public function __construct(array $context = []) {
        parent::__construct($context);

        $this->webRoot = $this->getWebsiteSettings()->getWebRoot();
    }

    protected function getTemplatePath(): string {
        return 'sitemap.phtml';
    }

    /**
     * @return SitemapEntryDTO[]
     */
    protected function getSitemapPages(): array {
        return $this->context['pages'] ?? [];
    }

    protected function buildFullPageUrl(string $url): string {
        if (!$url || $url === '/') {
            return $this->webRoot;
        }

        if (str_starts_with($url, '/')) {
            return $this->webRoot . $url;
        }

        return $url;
    }
}
