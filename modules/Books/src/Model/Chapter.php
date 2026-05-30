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
 * @method Book|null getBook()
 * @method self setBook(Book|null $book)
 * @method string getTitle()
 * @method self setTitle(string $title)
 * @method array getContent()
 * @method self setContent(array $content)
 * @method int getPosition()
 * @method self setPosition(int $position)
 */
#[DS\Table('chapters')]
class Chapter extends AEntity {
    #[DS\Id]
    protected int $id;

    #[DS\Column]
    protected ?Book $book;

    #[DS\Column]
    protected string $title = '';

    #[DS\Column]
    protected array $content = [];

    #[DS\Column]
    protected int $position = 0;
}
