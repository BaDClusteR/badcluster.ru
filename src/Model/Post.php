<?php

namespace BC\Model;

use DateTime;
use Runway\DataStorage\Attribute as DS;
use Runway\Model\AEntity;

/**
 * @method int getId()
 * @method self setId(int $id)
 * @method string getTitle()
 * @method self setTitle(string $title)
 * @method DateTime getCreatedDate()
 * @method self setCreatedDate(DateTime $createdDate)
 * @method DateTime getPublishDate()
 * @method self setPublishDate(DateTime $publishDate)
 * @method DateTime getUpdatedDate()
 * @method self setUpdatedDate(DateTime $updatedDate)
 * @method array getContent()
 * @method self setContent(array $content)
 * @method bool getPublished()
 * @method self setPublished(bool $published)
 * @method string getSlug()
 * @method self setSlug(string $slug)
 */

#[DS\Table("posts")]
class Post extends AEntity
{
    #[DS\Id]
    protected int $id;

    #[DS\Column]
    protected string $title = '';

    #[DS\Column]
    protected DateTime $createdDate;

    #[DS\Column]
    protected DateTime $publishDate;

    #[DS\Column]
    protected DateTime $updatedDate;

    #[DS\Column]
    protected array $content;

    #[DS\Column]
    protected bool $published = false;

    #[DS\Column]
    protected string $slug = '';
}
