<?php

declare(strict_types=1);

namespace BC\Model;

use BC\Core\Trait\WebsiteSettingsTrait;
use DateTime;
use Runway\DataStorage\Attribute as DS;
use Runway\Model\AEntity;

/**
 * A standalone content page living at the site root: /about, /history and the like.
 *
 * @generated-model-helpers
 * @method int getId()
 * @method self setId(int $id)
 * @method string getTitle()
 * @method self setTitle(string $title)
 * @method string getShortTitle()
 * @method self setShortTitle(string $shortTitle)
 * @method array getContent()
 * @method self setContent(array $content)
 * @method string getSlug()
 * @method self setSlug(string $slug)
 * @method bool getPublished()
 * @method self setPublished(bool $published)
 * @method bool getIndexable()
 * @method self setIndexable(bool $indexable)
 * @method bool getTextBlock()
 * @method self setTextBlock(bool $textBlock)
 * @method \DateTime|null getPublishDate()
 * @method self setPublishDate(\DateTime|null $publishDate)
 * @method \DateTime getCreatedDate()
 * @method self setCreatedDate(\DateTime $createdDate)
 * @method string getMetaDescription()
 * @method self setMetaDescription(string $metaDescription)
 * @method string getBackLinkText()
 * @method self setBackLinkText(string $backLinkText)
 * @method string getBackLinkUrl()
 * @method self setBackLinkUrl(string $backLinkUrl)
 */
#[DS\Table('static_pages')]
class StaticPage extends AEntity {
    use WebsiteSettingsTrait;

    #[DS\Id]
    protected int $id;

    #[DS\Column]
    protected string $title = '';

    /** Optional stand-in for the title in the SEO title. Falls back to `title`. */
    #[DS\Column]
    protected string $shortTitle = '';

    #[DS\Column]
    protected array $content;

    #[DS\Column]
    protected string $slug = '';

    #[DS\Column]
    protected bool $published = false;

    /** Off puts a robots noindex on the page and drops it from the sitemap. */
    #[DS\Column]
    protected bool $indexable = true;

    /**
     * Whether the content container carries the `text-block` class. Pages that
     * bring their own layout (the /me markup, say) need it off.
     */
    #[DS\Column]
    protected bool $textBlock = true;

    /** Optional, and deliberately not part of the visibility rules — see the controller. */
    #[DS\Column]
    protected ?DateTime $publishDate = null;

    /** Set once when the page is created; never exposed in the admin UI. */
    #[DS\Column]
    protected DateTime $createdDate;

    #[DS\Column]
    protected string $metaDescription = '';

    /** Both halves are optional; the link only renders when both are filled in. */
    #[DS\Column]
    protected string $backLinkText = '';

    #[DS\Column]
    protected string $backLinkUrl = '';

    /**
     * The page rendered at the site root instead of the hardcoded home page.
     * A reserved slug rather than a flag — see BC\Controller\Index.
     */
    public const string HOME_SLUG = 'home';

    public function isHomePage(): bool {
        return strtolower($this->getSlug()) === self::HOME_SLUG;
    }

    public function getUrl(): string {
        if ($this->isHomePage()) {
            return $this->getWebsiteSettings()->getWebRoot() . '/';
        }

        return sprintf(
            '%s/%s',
            $this->getWebsiteSettings()->getWebRoot(),
            $this->getSlug()
        );
    }
}
