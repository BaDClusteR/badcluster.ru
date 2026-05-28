<?php

declare(strict_types=1);

namespace BC\Modules\Games\Widget\Page;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Modules\Games\Model\Game;
use BC\Modules\Games\Model\GameMaterial;
use BC\Modules\Games\Widget\GameMaterial as GameMaterialWidget;
use BC\Widget\AWidget;
use BC\Widget\DTO\BackLinkDTO;
use BC\Widget\Page\APageWithBlocks;

class GameMaterialPage extends APageWithBlocks {
    protected Game $game;
    protected GameMaterial $material;

    protected function applyContext(array $context): void {
        parent::applyContext($context);

        if (($this->context['game'] ?? null) instanceof Game) {
            $this->game = $this->context['game'];
        }

        if (($this->context['material'] ?? null) instanceof GameMaterial) {
            $this->material = $this->context['material'];
        }
    }

    public function getHeader(): string {
        return '';
    }

    public function getTitle(): string {
        return $this->material->getTitle() . ' :: ' . $this->game->getTitle() . ' :: ' . parent::getTitle();
    }

    public function getMetaDescription(): string {
        return $this->material->getAnnotation();
    }

    public function getCanonicalUrl(): string {
        return $this->getWebRoot() . '/games/' . $this->game->getSlug() . '/' . $this->material->getSlug();
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): array {
        return [];
    }

    public function getMetaTitle(): string {
        return $this->material->getTitle() . ' - ' . $this->game->getTitle() . ' - ' . parent::getMetaTitle();
    }

    public function getMainWidget(): AWidget {
        return new GameMaterialWidget([
            'game'     => $this->game,
            'material' => $this->material,
        ]);
    }

    public function getBackLink(): ?BackLinkDTO {
        return new BackLinkDTO(
            url: $this->getWebRoot() . '/games',
            text: 'К списку игр'
        );
    }

    public static function getAssets(): array {
        return [
            new AssetDTO(
                'game',
                'css/modules/Games/game.css'
            ),
        ];
    }

    public function getCssBundles(): array {
        $list = parent::getCssBundles();
        $list[] = 'game';

        return $list;
    }

    public function getContentContainerCssClass(): string {
        return parent::getContentContainerCssClass() . ' text-block';
    }
}
