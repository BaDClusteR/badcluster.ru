<?php

declare(strict_types=1);

namespace BC\Modules\Music\Widget\Page;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Core\Trait\AuthTrait;
use BC\DTO\CommentsConfigDTO;
use BC\Modules\Music\Model\Album;
use BC\Modules\Music\Widget\Release;
use BC\Widget\AWidget;
use BC\Widget\DTO\BackLinkDTO;
use BC\Widget\Page\APage;

class ReleasePage extends APage {
    use AuthTrait;

    protected Album $album;

    protected function applyContext(array $context): void {
        parent::applyContext($context);

        if (($this->context['album'] ?? null) instanceof Album) {
            $this->album = $this->context['album'];
        }
    }

    public function getHeader(): string {
        return '';
    }

    public function getTitle(): string {
        return $this->album->getTitle() . ' :: Музыка :: ' . $this->getTitleBase();
    }

    public function getMetaDescription(): string {
        return $this->album->getShortAnnotation();
    }

    public function getCanonicalUrl(): string {
        return $this->getWebRoot() . '/music/' . $this->album->getSlug();
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): array {
        return [];
    }

    public function getMetaTitle(): string {
        return $this->album->getTitle() . ' — Музыка — ' . $this->getMetaTitleBase();
    }

    public function getMainWidget(): AWidget {
        return new Release(['album' => $this->album]);
    }

    public function getBackLink(): ?BackLinkDTO {
        return new BackLinkDTO(
            url: $this->getWebRoot() . '/music',
            text: 'К сборникам'
        );
    }

    public static function getAssets(): array {
        return [
            new AssetDTO(
                'release',
                'css/modules/Music/release.css'
            ),
            new AssetDTO(
                'audio',
                'js/modules/Music/audio.js'
            )
        ];
    }

    public function getCssBundles(): array {
        $list = parent::getCssBundles();
        $list[] = 'release';

        return $list;
    }

    public function getJsBundles(): array {
        $list = parent::getJsBundles();

        $list[] = 'audio';

        return $list;
    }
}
