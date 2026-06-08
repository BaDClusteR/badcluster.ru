<?php

declare(strict_types=1);

namespace BC\Modules\Books\Model;

use BC\Core\Trait\WebsiteSettingsTrait;
use BC\Model\Media;
use BC\Modules\Games\Model\GameMaterial;
use DateTime;
use Runway\DataStorage\Attribute as DS;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Model\AEntity;
use Runway\Model\Exception\ModelException;

/**
 * @generated-model-helpers
 * @method int getId()
 * @method self setId(int $id)
 * @method Book getBook()
 * @method self setBook(Book $book)
 * @method string getTitle()
 * @method self setTitle(string $title)
 * @method array getContent()
 * @method self setContent(array $content)
 * @method int getPosition()
 * @method self setPosition(int $position)
 * @method bool getPublished()
 * @method self setPublished(bool $published)
 * @method DateTime getAddedDate()
 * @method self setAddedDate(DateTime $addedDate)
 * @method DateTime getUpdateDate()
 * @method self setUpdateDate(DateTime $updateDate)
 * @method string getSlug()
 * @method self setSlug(string $slug)
 * @method string getPart()
 * @method self setPart(string $part)
 */
#[DS\Table('chapters')]
class Chapter extends AEntity {
    #[DS\Id]
    protected int $id;

    #[DS\Column]
    protected Book $book;

    #[DS\Column]
    protected string $title = '';

    #[DS\Column]
    protected array $content = [];

    #[DS\Column]
    protected int $position = 0;

    #[DS\Column]
    protected bool $published = false;

    #[DS\Column]
    protected DateTime $addedDate;

    #[DS\Column]
    protected DateTime $updateDate;

    #[DS\Column]
    protected string $slug = '';

    #[DS\Column]
    protected string $part = '';

    public function getUrl(): string {
        return $this->getBook()->getUrl() . '/' . $this->getSlug();
    }
}
