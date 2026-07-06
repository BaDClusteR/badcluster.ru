<?php

namespace BC\Widget\Page;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Widget\AWidget;
use BC\Widget\Page404 as Page404Widget;

class Page404 extends APage {
    public function getHeader(): string {
        return '';
    }

    public function getMetaDescription(): string {
        return '';
    }

    public function getCanonicalUrl(): string {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): array {
        return [];
    }

    public function getMainWidget(): AWidget {
        return new Page404Widget();
    }

    public static function getAssets(): array {
        return [
            new AssetDTO(
                '404',
                'css/404.css'
            )
        ];
    }

    public function getCssBundles(): array {
        $list = parent::getCssBundles();

        $list[] = '404';

        return $list;
    }
}
