<?php

namespace BC\Modules\Games\Provider;

use BC\DTO\SitemapEntryDTO;
use BC\Modules\Games\Model\GameMaterial;
use BC\Provider\ISitemapPagesProvider;
use Runway\Exception\Exception;

class SitemapPagesProvider implements ISitemapPagesProvider {

    /**
     * @inheritDoc
     */
    public function getSitemapPages(): array {
        $result = [
            new SitemapEntryDTO('/games')
        ];

        try {
            /** @var GameMaterial $material */
            foreach (GameMaterial::iterate() as $material) {
                if ($material->isFile()) {
                    $result[] = new SitemapEntryDTO($material->getMaterialUrl());
                }
            }
        } catch (Exception) {
        } finally {
            return $result;
        }
    }
}
