<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Widget;

use BC\Core\Trait\AuthTrait;
use BC\Core\Trait\DateConverterTrait;
use BC\Core\Trait\WebsiteSettingsTrait;
use BC\Modules\Blog\Model\Post;
use BC\Modules\Blog\Provider\IPostsProvider;
use BC\Widget\AWidget;
use BC\Widget\Common\Picture;
use DateTime;
use Runway\Singleton\Container;

class Posts extends AWidget {
    use AuthTrait;
    use DateConverterTrait;
    use WebsiteSettingsTrait;

    /**
     * @return iterable<Post>
     */
    protected function getPosts(): iterable {
        $posts = $this->getPostsProvider()->getPosts(
            $this->getTag(),
            $this->getPage(),
            !$this->getAuth()->isAuthenticated()
        );

        if ($posts) {
            yield from $posts;
        }

        yield;
    }

    protected function getTemplatePath(): string {
        return 'modules/Blog/posts.phtml';
    }

    protected function getDateValue(?DateTime $date): string {
        return $date
            ? $this->getDateConverter()->toIsoFormat($date)
            : '';
    }

    protected function getHumanReadableDate(?DateTime $date): string {
        return $date
            ? $this->getDateConverter()->toShortForm($date)
            : '';
    }

    protected function renderMiniature(Post $post): string {
        if ($miniature = $post->getCover()) {
            return new Picture([
                'image'        => $miniature,
                'pictureClass' => 'post__miniature',
                'lazyLoad'     => true,
                'breakpoints'  => [
                    -1 => 200,
                ],
            ])->render();
        }

        return '';
    }

    protected function getPage(): int {
        return max(1, (int) ($this->context['pageNum'] ?? 1));
    }

    private function getTag(): string {
        return (string) ($this->context['tag'] ?? '');
    }

    protected function getPages(): int {
        $showBy = $this->getPostsProvider()->getShowBy();

        return $showBy === 0
            ? 1
            : max(1, (int) ceil(($this->context['total'] ?? 0) / $showBy));
    }

    protected function getFirstPageUrl(): string {
        $tag = $this->getTag();
        $postfix = $tag
            ? "/tag/$tag"
            : '';

        return sprintf(
            '%s/blog%s',
            $this->getWebsiteSettings()->getWebRoot(),
            $postfix
        );
    }

    private function getPostsProvider(): IPostsProvider {
        return Container::getInstance()->getService(IPostsProvider::class);
    }
}
