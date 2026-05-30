<?php

declare(strict_types=1);

namespace BC\Modules\Books\Model;

use BC\Model\Media;
use DateTime;
use Runway\DataStorage\Attribute as DS;
use Runway\Model\AEntity;

/**
 * @generated-model-helpers
 * @method int getId()
 * @method self setId(int $id)
 * @method string getSlug()
 * @method self setSlug(string $slug)
 * @method string getTitle()
 * @method self setTitle(string $title)
 * @method Media|null getCover()
 * @method self setCover(Media|null $cover)
 * @method string|null getAuthor()
 * @method self setAuthor(string|null $author)
 * @method string getAnnotation()
 * @method self setAnnotation(string $annotation)
 * @method string getShortAnnotation()
 * @method self setShortAnnotation(string $shortAnnotation)
 * @method string getType()
 * @method self setType(string $type)
 * @method DateTime getLastUpdateDate()
 * @method self setLastUpdateDate(DateTime $lastUpdateDate)
 * @method array getTechnicalInfo()
 * @method self setTechnicalInfo(array $technicalInfo)
 * @method string|null getGroup()
 * @method self setGroup(string|null $group)
 * @method BookFormat[] getFormats()
 */
#[DS\Table('books')]
class Book extends AEntity {
    public const string TYPE_AUTEUR = 'A';

    public const string TYPE_TRANSLATION = 'T';

    #[DS\Id]
    protected int $id;

    #[DS\Column]
    protected string $slug = '';

    #[DS\Column]
    protected string $title = '';

    #[DS\Column]
    protected ?Media $cover = null;

    #[DS\Column]
    protected ?string $author = null;

    #[DS\Column]
    protected string $annotation = '';

    #[DS\Column]
    protected string $shortAnnotation = '';

    #[DS\Column]
    protected string $type = '';

    #[DS\Column]
    protected DateTime $lastUpdateDate;

    #[DS\Column]
    protected array $technicalInfo = [];

    #[DS\Column]
    protected ?string $group = null;

    #[DS\Reference(refModel: BookFormat::class, refProp: 'book')]
    protected ?array $formats = null;
}
