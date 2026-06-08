<?php

declare(strict_types=1);

namespace BC\Modules\Books\Widget\Page;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Modules\Books\Model\Chapter;
use BC\Modules\Books\Widget\Chapter as ChapterWidget;
use BC\Widget\AWidget;
use BC\Widget\DTO\BackLinkDTO;
use BC\Widget\Page\APageWithBlocks;

class ChapterPage extends APageWithBlocks {
    protected Chapter $chapter;

    public function getHeader(): string {
        return $this->chapter->getTitle();
    }

    public function getMetaTitle(): string {
        return $this->chapter->getTitle() . ' — ' . $this->chapter->getBook()->getTitle() . ' — ' . $this->getMetaTitleBase();
    }

    public function getTitle(): string {
        return $this->chapter->getTitle() . ' :: ' . $this->chapter->getBook()->getTitle() . ' :: ' . $this->getTitleBase();
    }

    public function getMetaDescription(): string {
        return '';
    }

    public function getCanonicalUrl(): string {
        return $this->chapter->getUrl();
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): array {
        return [];
    }

    public function getBackLink(): ?BackLinkDTO {
        return new BackLinkDTO(
            url: $this->chapter->getBook()->getUrl(),
            text: 'К оглавлению'
        );
    }

    public function getMainWidget(): AWidget {
        return new ChapterWidget(['chapter' => $this->chapter]);
    }

    public static function getAssets(): array {
        $list = parent::getAssets();

        $list[] = new AssetDTO('chapter', 'css/modules/Books/chapter.css');

        return $list;
    }

    public function getCssBundles(): array {
        $list = parent::getCssBundles();

        $list[] = 'chapter';

        return $list;
    }

    protected function applyContext(array $context): void {
        parent::applyContext($context);

        $this->chapter = $this->context['chapter'];
    }

    public function getContentContainerCssClass(): string {
        return parent::getContentContainerCssClass() . ' text-block';
    }
}
