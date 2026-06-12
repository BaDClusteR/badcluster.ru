<?php

declare(strict_types=1);

namespace BC\Modules\Books\Model;

use BC\Modules\Books\Core\Trait\BookFormatProviderTrait;
use DateTime;
use Runway\DataStorage\Attribute as DS;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Model\AEntity;
use Runway\Model\Exception\ModelException;

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
 * @method string getPostfix()
 * @method self setPostfix(string $postfix)
 */
#[DS\Table('book_formats')]
class BookFormat extends AEntity {
    use BookFormatProviderTrait;

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

    #[DS\Column]
    protected string $postfix = '';

    public function getUrl(): string {
        return dirname($this->getBook()->getUrl()) . '/' . $this->getFilename();
    }

    /**
     * @throws DBException
     * @throws ModelException
     * @throws QueryBuilderException
     */
    public function generateDump(): void {
        $renderer = $this->getBookFormatProvider()->getFormat($this->getType());

        if ($renderer) {
            $dump = $renderer->generateBook($this->getBook());
            $this->setDump($dump)
                 ->setSize(strlen($dump))
                 ->setDateGenerated(new DateTime('now'))
                 ->persist();
        } else {
            $this->clearDump();
        }
    }

    /**
     * @throws DBException
     * @throws ModelException
     * @throws QueryBuilderException
     */
    public function clearDump(): void {
        $this->setDump('')
             ->setSize(0)
             ->setDateGenerated(new DateTime('now'))
             ->persist();
    }
}
