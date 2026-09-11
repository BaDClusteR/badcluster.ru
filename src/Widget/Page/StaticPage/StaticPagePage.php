<?php

declare(strict_types=1);

namespace BC\Widget\Page\StaticPage;

use BC\Core\Shortcode\IShortcodeResolver;
use BC\Core\Trait\WebsiteSettingsTrait;
use BC\Model\StaticPage as StaticPageModel;
use BC\Widget\AWidget;
use BC\Widget\DTO\BackLinkDTO;
use BC\Widget\DTO\MetaTagDTO;
use BC\Widget\Page\APageWithBlocks;
use BC\Widget\Page\Home\HomePage;
use Runway\Exception\RuntimeException;
use Runway\Singleton\Container;

class StaticPagePage extends APageWithBlocks {
    use WebsiteSettingsTrait;

    private ?StaticPageModel $staticPage = null;

    private ?HomePage $homePage = null;

    protected function applyContext(array $context): void {
        parent::applyContext($context);

        if (($context['staticPage'] ?? null) instanceof StaticPageModel) {
            $this->staticPage = $context['staticPage'];
        }

        if (empty($this->context['isHomePage']) && $this->homePage) {
            $this->homePage = null;
        } elseif (!empty($this->context['isHomePage']) && !$this->homePage) {
            $this->homePage = new HomePage();
        }

        if (!$this->staticPage) {
            throw new RuntimeException(
                __METHOD__ . ': staticPage is not set or not an instance of ' . StaticPageModel::class
            );
        }
    }

    public function getHeader(): string {
        return $this->homePage?->getHeader()
               ?? $this->expand($this->staticPage->getTitle());
    }

    public function getDescription(): array {
        return [];
    }

    public function getMainWidget(): AWidget {
        return new StaticPageContent([
            'staticPage' => $this->staticPage,
        ]);
    }

    public function getTitle(): string {
        return $this->homePage?->getTitle()
               ?? $this->getSeoTitle() . ' :: ' . $this->getTitleBase();
    }

    public function getMetaTitle(): string {
        return $this->homePage?->getMetaTitle()
               ?? $this->getSeoTitle() . ' — ' . $this->getMetaTitleBase();
    }

    /** The page's own heading can be long; shortTitle is the SEO stand-in. */
    private function getSeoTitle(): string {
        return $this->expand(
            $this->staticPage->getShortTitle() ?: $this->staticPage->getTitle()
        );
    }

    /**
     * Titles go through the shortcodes too — otherwise a heading like
     * "История одного сайта: [site_age]" would show raw brackets.
     */
    private function expand(string $text): string {
        return Container::getInstance()
                        ->getService(IShortcodeResolver::class)
                        ->expand($text);
    }

    public function getMetaDescription(): string {
        return $this->staticPage->getMetaDescription();
    }

    public function getCanonicalUrl(): string {
        return $this->homePage?->getCanonicalUrl()
               ?? $this->getWebsiteSettings()->getWebRoot() . '/' . $this->staticPage->getSlug();
    }

    public function getOpenGraphType(): string {
        return $this->homePage?->getOpenGraphType()
               ?? 'article';
    }

    /**
     * The publish date does not gate visibility — it only feeds the markup here.
     */
    public function getMetaTags(): array {
        if ($this->homePage) {
            return $this->homePage->getMetaTags();
        }

        $list = parent::getMetaTags();

        // Same tag APageNoIndexed emits — here it is per page, not per class,
        // because indexability is a property of the content.
        if (!$this->staticPage->getIndexable()) {
            $list[] = new MetaTagDTO(
                name: 'robots',
                content: 'noindex,nofollow'
            );
        }

        if ($publishDate = $this->staticPage->getPublishDate()) {
            $list[] = new MetaTagDTO(
                name: 'og:article:published_time',
                content: $publishDate->format(DATE_ATOM),
            );
        }

        return $list;
    }

    /**
     * Both halves are optional and only make sense together — a link with no
     * text, or text pointing nowhere, is not worth rendering.
     */
    public function getBackLink(): ?BackLinkDTO {
        if ($this->homePage) {
            return $this->homePage->getBackLink();
        }

        $text = trim($this->staticPage->getBackLinkText());
        $url = trim($this->staticPage->getBackLinkUrl());

        if ($text === '' || $url === '') {
            return null;
        }

        return new BackLinkDTO(
            url: $url,
            text: $text
        );
    }

    /**
     * `static-page` is what css/static-page.css scopes itself to — the content
     * itself is rendered as bare blocks, with no wrapper of its own to hang it on.
     *
     * `text-block` sets up the reading grid most pages want, but a page that
     * brings its own layout needs the container left alone.
     */
    public function getContentContainerCssClass(): string {
        if ($this->homePage) {
            return $this->homePage->getContentContainerCssClass();
        }

        $classes = [parent::getContentContainerCssClass(), 'static-page'];

        if ($this->staticPage->getTextBlock()) {
            $classes[] = 'text-block';
        }

        return implode(' ', $classes);
    }
}
