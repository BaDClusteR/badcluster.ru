<?php

namespace BC\Modules\Blog\Widget\Page;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Core\Trait\WebsiteSettingsTrait;
use BC\DTO\CommentsConfigDTO;
use BC\Modules\Blog\Model\Post;
use BC\Modules\Blog\Model\Post as PostModel;
use BC\Modules\Blog\Widget\Post as PostWidget;
use BC\Widget\AWidget;
use BC\Widget\DTO\BackLinkDTO;
use BC\Widget\DTO\MetaTagDTO;
use BC\Widget\Page\APageWithBlocks;
use BC\Widget\Page\IPageWithComments;
use Runway\Exception\RuntimeException;

class PostPage extends APageWithBlocks implements IPageWithComments {
    use WebsiteSettingsTrait;

    private ?Post $post = null;

    /**
     * @inheritDoc
     */
    public static function getAssets(): array {
        return [
            new AssetDTO(
                'post',
                'css/modules/Blog/post.css'
            )
        ];
    }

    protected function applyContext(array $context): void {
        parent::applyContext($context);

        if (!$this->post && !(($context['post'] ?? null) instanceof PostModel)) {
            throw new RuntimeException(__METHOD__ . ': post is not set or not an instance of ' . PostModel::class);
        }

        $this->post ??= $context['post'];
    }

    public function getHeader(): string {
        return '';
    }

    public function getDescription(): array {
        return [];
    }

    public function getMainWidget(): AWidget {
        return new PostWidget([
            'post' => $this->post,
            'page' => $this
        ]);
    }

    public function getBackLink(): ?BackLinkDTO {
        return new BackLinkDTO(
            url: $this->getWebsiteSettings()->getWebRoot() . '/blog',
            text: 'Назад к постам'
        );
    }

    public function getCssBundles(): array {
        $list = parent::getCssBundles();
        $list[] = 'post';

        return $list;
    }

    public function getTitle(): string {
        return $this->getPostTitle() . ' :: Блог :: ' . parent::getTitle();
    }

    private function getPostTitle(): string {
        return $this->post->getShortTitle() ?: $this->post->getTitle();
    }

    public function getMetaDescription(): string {
        return $this->post->getMetaDescription() ?: $this->post->getAnnotation();
    }

    public function getMetaTitle(): string {
        return $this->getPostTitle() . ' — ' . parent::getMetaTitle();
    }

    public function getOpenGraphType(): string {
        return 'article';
    }

    public function getMetaTags(): array {
        $list = [
            ...parent::getMetaTags(),
            new MetaTagDTO(
                name: 'og:article:author',
                content: 'BaD ClusteR'
            ),
            new MetaTagDTO(
                name: 'og:article:published_time',
                content: $this->post->getPublishDate()->format(DATE_ATOM),
            )
        ];

        if ($modified = $this->post->getUpdateDate()) {
            $list[] = new MetaTagDTO(
                name: 'og:article:modified_time',
                content: $modified->format(DATE_ATOM),
            );
        }

        foreach ($this->post->getTags() as $tag) {
            $list[] = new MetaTagDTO(
                name: 'og:article:tag',
                content: $tag->getTitle(),
            );
        }

        return $list;
    }

    public function getCommentsConfig(): CommentsConfigDTO {
        return new CommentsConfigDTO(
            comments: [],
            emptyPhrase: 'Пока никто не комментировал. Есть мысли? Делитесь, я читаю всё :)',
            pageType: 'post',
            pageId: (int) $this->post?->getId(),
        );
    }

    public function getNoCommentsPhrase(): string {
        return 'Пока никто не комментировал. Есть мысли? Делитесь, я читаю всё :)';
    }

    public function getJsBundles(): array {
        $list = parent::getJsBundles();

        $list[] = 'comments';
        $list[] = 'toast';

        return $list;
    }

    public function getPageId(): int {
        return (int) $this->post?->getId();
    }

    public function getPageType(): string {
        return 'post';
    }
}
