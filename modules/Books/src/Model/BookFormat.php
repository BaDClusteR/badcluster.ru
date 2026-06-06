<?php

declare(strict_types=1);

namespace BC\Modules\Books\Model;

use DateTime;
use Runway\DataStorage\Attribute as DS;
use Runway\Model\AEntity;

/**
 * @generated-model-helpers
 *
 * @method int getId()
 * @method self setId(int $id)
 * @method Book|null getBook()
 * @method self setBook(Book|null $book)
 * @method string getType()
 * @method self setType(string $type)
 * @method bool getAllowed()
 * @method self setAllowed(bool $allowed)
 * @method string getFilename()
 * @method self setFilename(string $filename)
 * @method int getSize()
 * @method self setSize(int $size)
 * @method string getDump()
 * @method self setDump(string $dump)
 * @method DateTime getDateGenerated()
 * @method self setDateGenerated(DateTime $dateGenerated)
 */
#[DS\Table('book_formats')]
class BookFormat extends AEntity {
    #[DS\Id]
    protected int $id;

    #[DS\Column]
    protected ?Book $book;

    #[DS\Column]
    protected string $type = '';

    #[DS\Column]
    protected bool $allowed = false;

    #[DS\Column]
    protected string $filename = '';

    #[DS\Column]
    protected int $size = 0;

    #[DS\Column]
    protected string $dump = '';

    #[DS\Column]
    protected DateTime $dateGenerated;
}
