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

    public function getCanonicalUrl(): string {
        return '/cringe';
    }

    public function getTitle(): string {
        return 'Музей кринжа :: ' . $this->getTitleBase();
    }

    public function getMetaTitle(): string {
        return 'Музей Кринжа — ' . $this->getMetaTitleBase();
    }

    public function getMetaDescription(): string {
        return 'Стыдный уголок. Здесь хранятся мои ранние творческие потуги, которые слишком стыдно показывать, но жаль удалять насовсем.';
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
