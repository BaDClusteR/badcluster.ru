<?php

namespace BC\Widget\Page\About;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Widget\AWidget;
use BC\Widget\Page\APage;

class AboutMePage extends APage {
    public function getHeader(): string {
        return 'Привет!';
    }

    public function getMetaDescription(): string {
        return '';
    }

    public function getCanonicalUrl(): string {
        return '/me';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): array {
        return [];
    }

    public function getMainWidget(): AWidget {
        return new AboutMe();
    }

    public static function getAssets(): array {
        return [
            new AssetDTO('about_me', 'css/about/about.css')
        ];
    }

    public function getCssBundles(): array {
        $bundles = parent::getCssBundles();

        $bundles[] = 'blocks';
        $bundles[] = 'about_me';

        return $bundles;
    }
}
