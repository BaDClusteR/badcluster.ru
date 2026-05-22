<?php

namespace BC\Widget\Common;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Core\Trait\AssetBuilderTrait;
use BC\Core\Trait\AuthTrait;
use BC\DTO\CommentsConfigDTO;
use BC\Widget\AWidget;
use BC\Core\DTO\CommentDTO;
use BC\Widget\IAssetProvider;
use BC\Widget\Page\APage;
use BC\Widget\Page\IPageWithComments;

class Comments extends AWidget implements IAssetProvider {
    use AssetBuilderTrait;
    use AuthTrait;

    private ?APage $page = null;
    private ?CommentsConfigDTO $config = null;

    protected function applyContext(array $context): void {
        parent::applyContext($context);

        if (($this->context['page'] ?? null) instanceof APage) {
            $this->page = $this->context['page'];

            $this->config = ($this->page instanceof IPageWithComments)
                ? $this->page->getCommentsConfig()
                : null;
        }
    }

    protected function getTemplatePath(): string {
        return 'common/comments.phtml';
    }

    /**
     * @return CommentDTO[]
     */
    protected function getComments(): array {
        return (array) $this->config?->comments;
    }

    protected function getNoCommentsPhrase(): string {
        return $this->config?->emptyPhrase
               ?? 'Комментариев пока нет. Будьте первым!';
    }

    protected function getPageType(): string {
        return (string) $this->config?->pageType;
    }

    protected function getPageId(): int {
        return (int) $this->config?->pageId;
    }

    public static function getAssets(): array {
        return [
            new AssetDTO(
                bundle: 'comments',
                path: 'css/common/comments.css',
            ),
            new AssetDTO(
                bundle: 'comments-admin',
                path: 'css/common/comments-admin.css',
            ),
            new AssetDTO(
                bundle: 'toast',
                path: 'css/common/toast.css',
            ),
            new AssetDTO(
                bundle: 'comments',
                path: 'js/common/comments.js',
            ),
            new AssetDTO(
                bundle: 'comments-admin',
                path: 'js/common/comments-admin.js',
            ),
            new AssetDTO(
                bundle: 'toast',
                path: 'js/common/toast.js'
            )
        ];
    }

    protected function isAuthenticated(): bool {
        return $this->getAuth()->isAuthenticated();
    }

    protected function getDefaultNickname(): string {
        return $this->getAuth()->isAuthenticated()
            ? 'BaD ClusteR'
            : '';
    }
}
