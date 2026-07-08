<?php

namespace BC\Widget\Page\Photos;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Core\Trait\WebsiteSettingsTrait;
use BC\Widget\AWidget;
use BC\Widget\Page\APageNoIndexed;

class PhotosPage extends APageNoIndexed {
    use WebsiteSettingsTrait;

    public function getHeader(): string {
        return 'Фотоархив';
    }

    public function getCanonicalUrl(): string {
        return '/photos';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): array {
        return [
            'Оффлайн. Мой взгляд на мир через объектив фотокамеры. Никакой сложной концепции — просто красивые места, правильный свет и моменты из жизни, пойманные в кадр.'
        ];
    }

    public function getMainWidget(): AWidget {
        return new Photos();
    }

    public static function getAssets(): array {
        return [
            new AssetDTO('photos', 'css/about/photos.css'),
            new AssetDTO('photos', 'js/photos.js'),
        ];
    }

    public function getCssBundles(): array {
        return [
            ...parent::getCssBundles(),
            'photos',
            'blocks'
        ];
    }

    public function getCriticalJsBundles(): array {
        $bundles = parent::getCriticalJsBundles();

        $bundles[] = [
            'src'  => 'photos',
            'type' => 'module'
        ];

        return $bundles;
    }
}
