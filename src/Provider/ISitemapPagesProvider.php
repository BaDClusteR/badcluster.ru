<?php

namespace BC\Provider;

use BC\DTO\SitemapEntryDTO;

interface ISitemapPagesProvider {
    /**
     * @return SitemapEntryDTO[]
     */
    public function getSitemapPages(): array;
}
