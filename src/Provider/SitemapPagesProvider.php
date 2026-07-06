<?php

namespace BC\Provider;

use BC\Core\Trait\WebsiteSettingsTrait;
use BC\DTO\SitemapEntryDTO;

class SitemapPagesProvider implements ISitemapPagesProvider {
    use WebsiteSettingsTrait;

    /**
     * @inheritDoc
     */
    public function getSitemapPages(): array {
        return [
            new SitemapEntryDTO('/'),
            new SitemapEntryDTO('/me')
        ];
    }
}
