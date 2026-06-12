<?php

declare(strict_types=1);

namespace BC\Widget\Page;

use BC\Core\Action\Comments\IGetCommentsAction;
use BC\Core\Action\DTO\GetCommentsRequest;
use BC\Core\Asset\DTO\AssetDTO;
use BC\Core\DTO\CommentDTO;
use BC\Core\Trait\WebsiteSettingsTrait;
use BC\DTO\PageImageDTO;
use BC\Widget\AWidget;
use BC\Widget\DTO\BackLinkDTO;
use BC\Widget\DTO\MetaTagDTO;
use BC\Widget\IAssetProvider;
use Runway\Exception\Exception;
use Runway\Singleton\Container;

abstract class APage extends AWidget implements IAssetProvider {
    use WebsiteSettingsTrait;

    abstract public function getHeader(): string;

    abstract public function getMetaDescription(): string;

    abstract public function getCanonicalUrl(): string;

    public function getPageImage(): ?PageImageDTO {
        return null;
    }

    public function getOpenGraphType(): string {
        return 'website';
        //article, book, music.song, music.album
    }

    /**
     * @return MetaTagDTO[]
     */
    public function getMetaTags(): array {
        return [];
    }

    public function getTitle(): string {
        $title = '';
        if ($header = $this->getHeader()) {
            $title = "$header :: ";
        }

        return $title . $this->getTitleBase();
    }

    protected function getTitleBase(): string {
        return 'BaD ClusteR';
    }

    public function getMetaTitle(): string {
        $metaTitle = '';
        if ($header = $this->getHeader()) {
            $metaTitle = "$header — ";
        }

        return $metaTitle . $this->getMetaTitleBase();
    }

    protected function getMetaTitleBase(): string {
        return 'Цифровой архив BaD ClusteR\'а';
    }

    /**
     * @return string[]
     */
    abstract public function getDescription(): array;

    abstract public function getMainWidget(): AWidget;

    protected function getTemplatePath(): string {
        return 'page.phtml';
    }

    public function getBackLink(): ?BackLinkDTO {
        return null;
    }

    public function getContentContainerCssClass(): string {
        return 'content-container';
    }

    public static function getAssets(): array {
        return [
            new AssetDTO('critical', 'js/critical/singleton.js', -100),
            new AssetDTO('critical', 'js/critical/event-dispatcher.js', -50),
            new AssetDTO('critical', 'js/critical/theme.js'),

            new AssetDTO('scripts', 'js/common/theme-switcher.js'),
            new AssetDTO('scripts', 'js/common/header.js'),
            new AssetDTO('scripts', 'js/common/scripts.js'),
            new AssetDTO('scripts', 'js/common/tabs.js'),

            new AssetDTO('core', 'css/core/reset.css', -100),
            new AssetDTO('core', 'css/core/font.css', -50),
            new AssetDTO('core', 'css/core/style.css', 0),
            new AssetDTO('core', 'css/core/tooltip.css', 100),

            new AssetDTO('footer', 'css/footer.css'),
        ];
    }

    public function getCssBundles(): array {
        return ['core'];
    }

    public function getCriticalJsBundles(): array {
        return ['critical'];
    }

    /**
     * @return string[]
     */
    public function getJsBundles(): array {
        return [];
    }

    protected function getWebRoot(): string {
        return $this->getWebsiteSettings()->getWebRoot();
    }

    /**
     * @return CommentDTO[]
     */
    protected function getComments(string $pageType, ?int $pageId): array {
        if (!$pageType || !$pageId) {
            return [];
        }

        $action = Container::getInstance()->getService(IGetCommentsAction::class);

        try {
            return $action->run(
                new GetCommentsRequest(
                    pageType: $pageType,
                    pageId: $pageId
                )
            )->comments;
        } catch (Exception $e) {
            $this->getLogger()->error(
                "Error while getting comments: {$e->getMessage()}",
                [
                    'pageType' => $pageType,
                    'pageId'   => $pageId,
                ]
            );

            return [];
        }
    }
}
