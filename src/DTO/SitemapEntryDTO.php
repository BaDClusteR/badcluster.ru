<?php

declare(strict_types=1);

namespace BC\DTO;

readonly class SitemapEntryDTO {
    public function __construct(
        public string $url,
        public SitemapEntryChangeFreqEnum $changeFreq = SitemapEntryChangeFreqEnum::DAILY
    ) {
    }
}
