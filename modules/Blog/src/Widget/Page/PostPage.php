<?php

namespace BC\Modules\Blog\Widget\Page;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Core\Trait\WebsiteSettingsTrait;
use BC\Modules\Blog\Model\Post;
use BC\Modules\Blog\Model\Post as PostModel;
use BC\Modules\Blog\Widget\Post as PostWidget;
use BC\Widget\AWidget;
use BC\Widget\DTO\BackLinkDTO;
use BC\Widget\Page\APage;
use Runway\Exception\RuntimeException;

class PostPage extends APage
{
    use WebsiteSettingsTrait;

    private ?Post $post = null;

    /**
     * @inheritDoc
     */
    public static function getAssets(): array
    {
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
            throw new RuntimeException(__METHOD__ . ": post is not set or not an instance of " . PostModel::class);
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
        return new PostWidget(['post' => $this->post]);
    }

    public function getBackLink(): ?BackLinkDTO {
        return new BackLinkDTO(
            url: $this->getWebsiteSettings()->getWebRoot() . "/blog",
            text: "К списку постов"
        );
    }

    public function getCssBundles(): array {
        $list = parent::getCssBundles();
        $list[] = 'post';

        return $list;
    }
}
