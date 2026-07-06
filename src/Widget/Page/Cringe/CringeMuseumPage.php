<?php

namespace BC\Widget\Page\Cringe;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Widget\AWidget;
use BC\Widget\DTO\BackLinkDTO;
use BC\Widget\Page\APageNoIndexed;

class CringeMuseumPage extends APageNoIndexed {
    public function getHeader(): string {
        return '';
    }

    public function getMetaDescription(): string {
        return '';
    }

    public function getCanonicalUrl(): string {
        return '/cringe';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): array {
        return [];
    }

    public function getMainWidget(): AWidget {
        return new CringeMuseum();
    }

    public static function getAssets(): array {
        return [
            new AssetDTO(
                'about_cringe',
                'css/about/cringe.css'
            )
        ];
    }

    public function getBackLink(): ?BackLinkDTO {
        return new BackLinkDTO(
            url: '/history',
            text: 'Бежать отсюда'
        );
    }

    public function getCssBundles(): array {
        $bundles = parent::getCssBundles();

        $bundles[] = 'about_cringe';

        return $bundles;
    }
}
