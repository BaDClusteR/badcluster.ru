<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Model;

use BC\Core\Trait\WebsiteSettingsTrait;
use Runway\DataStorage\Attribute as DS;
use Runway\Model\AEntity;

/**
 * @generated-model-helpers
 * @method int getId()
 * @method self setId(int $id)
 * @method string getTitle()
 * @method self setTitle(string $title)
 * @method string getSlug()
 * @method self setSlug(string $slug)
 * @method string getDescription()
 * @method self setDescription(string $description)
 */
#[DS\Table('tags')]
class Tag extends AEntity {
    use WebsiteSettingsTrait;

    #[DS\Id]
    protected int $id;

    #[DS\Column]
    protected string $title = '';

    #[DS\Column]
    protected string $slug = '';

    #[DS\Column]
    protected string $description = '';

    public function getUrl(): string {
        return $this->getWebsiteSettings()->getWebRoot() . '/blog/tag/' . $this->slug;
    }
}
