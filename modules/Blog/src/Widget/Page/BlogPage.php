<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Widget\Page;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Modules\Blog\Model\Tag;
use BC\Modules\Blog\Widget\Posts;
use BC\Widget\AWidget;
use BC\Widget\Page\APage;
use Runway\Exception\Exception;

class BlogPage extends APage {
    private int $page = 1;

    private string $tag = '';

    private int $total = 0;

    protected function applyContext(array $context): void {
        parent::applyContext($context);

        if (array_key_exists('tag', $this->context)) {
            $this->tag = (string) $this->context['tag'];
        }

        if (array_key_exists('page', $this->context)) {
            $this->page = (int) $this->context['page'];
        }

        if (array_key_exists('total', $this->context)) {
            $this->total = (int) $this->context['total'];
        }
    }

    /**
     * @inheritDoc
     */
    public static function getAssets(): array {
        return [
            new AssetDTO(
                'posts',
                'css/modules/Blog/posts.css'
            ),
        ];
    }

    public function getHeader(): string {
        $header = ($tag = $this->getTag())
            ? "#{$tag->getTitle()}"
            : 'Блог';

        if ($this->page > 1) {
            $header .= ", страница $this->page";
        }

        return $header;
    }

    public function getTitle(): string {
        $result = $this->getHeader();

        if ($this->getTag()) {
            $result .= ' :: Блог';
        }

        return $result . ' :: ' . parent::getTitle();
    }

    public function getMetaDescription(): string {
        return 'Мысли о фильмах, играх и цифровая археология. Пишу о пройденном, копаюсь в игровом лоре, разбираю секреты, которые разработчики оставили за кадром.';
    }

    public function getDescription(): array {
        $firstPageDescription = ($tag = $this->getTag())
            ? explode('\n', $tag->getDescription())
            : [
                'Мысли о фильмах, играх, и капля цифровой археологии. Пишу о пройденном и просмотренном, по настроению копаюсь в лоре, разбираю игровые секреты.',
            ];

        return $this->page === 1
            ? $firstPageDescription
            : [];
    }

    public function getMetaTitle(): string {
        return $this->getHeader() . ' — ' . parent::getMetaTitle();
    }

    public function getMainWidget(): AWidget {
        return new Posts([
            'pageNum' => $this->page,
            'total'   => $this->total,
            'tag'     => $this->tag,
        ]);
    }

    public function getCssBundles(): array {
        $list = parent::getCssBundles();
        $list[] = 'posts';

        return $list;
    }

    protected function getTag(): ?Tag {
        try {
            return Tag::findOne([
                'slug' => $this->tag,
            ]);
        } catch (Exception) {
            return null;
        }
    }

    public function getCanonicalUrl(): string {
        $link = $this->getWebRoot() . '/blog';
        if ($tag = $this->getTag()) {
            $link .= '/tag/' . $tag->getSlug();
        }

        if ($this->page > 1) {
            $link .= '/page/' . $this->page;
        }

        return $link;
    }
}
