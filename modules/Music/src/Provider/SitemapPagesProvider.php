<?php

namespace BC\Modules\Music\Provider;

use BC\DTO\SitemapEntryChangeFreqEnum;
use BC\DTO\SitemapEntryDTO;
use BC\Modules\Music\Model\Album;
use BC\Provider\ISitemapPagesProvider;
use Runway\Exception\Exception;

class SitemapPagesProvider implements ISitemapPagesProvider {

    /**
     * @inheritDoc
     */
    public function getSitemapPages(): array {
        $result = [
            new SitemapEntryDTO('/music')
        ];

        try {
            /** @var Album $album */
            foreach (Album::iterate() as $album) {
                $result[] = new SitemapEntryDTO(
                    $album->getUrl(),
                    SitemapEntryChangeFreqEnum::MONTHLY
                );
            }
        } catch (Exception) {
        } finally {
            return $result;
        }
    }
}
