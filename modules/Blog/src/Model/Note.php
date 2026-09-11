<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Model;

use BC\Core\Trait\WebsiteSettingsTrait;
use DateTime;
use Runway\DataStorage\Attribute as DS;
use Runway\Model\AEntity;

/**
 * @generated-model-helpers
 * @method int getId()
 * @method self setId(int $id)
 * @method string getTitle()
 * @method self setTitle(string $title)
 * @method \DateTime getCreatedDate()
 * @method self setCreatedDate(\DateTime $createdDate)
 * @method \DateTime getPublishDate()
 * @method self setPublishDate(\DateTime $publishDate)
 * @method array getContent()
 * @method self setContent(array $content)
 * @method bool getPublished()
 * @method self setPublished(bool $published)
 * @method string getSlug()
 * @method self setSlug(string $slug)
 * @method string getMetaDescription()
 * @method self setMetaDescription(string $metaDescription)
 */
#[DS\Table('notes')]
class Note extends AEntity {
    use WebsiteSettingsTrait;

    #[DS\Id]
    protected int $id;

    #[DS\Column]
    protected string $title = '';

    #[DS\Column]
    protected DateTime $createdDate;

    #[DS\Column]
    protected DateTime $publishDate;

    #[DS\Column]
    protected array $content;

    #[DS\Column]
    protected bool $published = false;

    #[DS\Column]
    protected string $slug = '';

    #[DS\Column]
    protected string $metaDescription = '';

    public function getUrl(): string {
        return sprintf(
            '%s/notes/%s',
            $this->getWebsiteSettings()->getWebRoot(),
            $this->getSlug()
        );
    }
}
