<?php

namespace BC\Widget\Page\Screenshots;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Core\Trait\WebsiteSettingsTrait;
use BC\Widget\AWidget;
use BC\Widget\Page\APageNoIndexed;

class ScreenshotsPage extends APageNoIndexed {
    use WebsiteSettingsTrait;

    public function getHeader(): string {
        return 'Виртуальная фотография';
    }

    public function getCanonicalUrl(): string {
        return '/screenshots';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): array {
        $steamProfileUrl = $this->getWebsiteSettings()->getAdminContacts()->steam;

        return [
            'Охочусь за красивыми кадрами в придуманных мирах, лучшее выкладываю тут.',
            'Если вам понравилась подборка, заглядывайте в мой <a href="' . $steamProfileUrl . '" target="_blank">профиль Steam</a> — там собрана солидная коллекция красоты в играх, пасхалок и случайных багов физики :)'
        ];
    }

    public function getMainWidget(): AWidget {
        return new Screenshots();
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
