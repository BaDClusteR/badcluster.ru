<?php

namespace BC\Widget\Page\History;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Core\Trait\FormatterTrait;
use BC\Widget\AWidget;
use BC\Widget\Page\APageNoIndexed;
use DateTime;

class HistoryPage extends APageNoIndexed {
    use FormatterTrait;

    public function getTitle(): string {
        return 'История одного сайта :: ' . $this->getTitleBase();
    }

    public function getMetaTitle(): string {
        return 'История одного сайта — ' . $this->getMetaTitleBase();
    }

    public function getMetaDescription(): string {
        return 'История эволюции badcluster.ru — а заодно и его автора.';
    }

    public function getHeader(): string {
        $ageYears = new DateTime()->diff(new DateTime('2005-08-02'))->y;

        $age = $this->getFormatter()->formatAsWordForm(
            ($ageYears - ($ageYears % 5)),
            'год',
            'года',
            'лет'
        );

        $age = str_replace(' ', '&nbsp;', $age);

        return "История одного сайта: $age эволюции badcluster.ru";
    }

    public function getCanonicalUrl(): string {
        return '/history';
    }

    public function getContentContainerCssClass(): string {
        return parent::getContentContainerCssClass() . ' text-block';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): array {
        return [];
    }

    public function getMainWidget(): AWidget {
        return new History();
    }

    public static function getAssets(): array {
        return [
            new AssetDTO('about_history', 'css/about/history.css')
        ];
    }

    public function getCssBundles(): array {
        $bundles = parent::getCssBundles();

        $bundles[] = 'blocks';
        $bundles[] = 'about_history';

        return $bundles;
    }

    public function getCriticalJsBundles(): array {
        $bundles = parent::getCriticalJsBundles();

        $bundles[] = [
            'src'  => 'blocks',
            'type' => 'module'
        ];

        return $bundles;
    }
}
