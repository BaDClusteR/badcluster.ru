<?php

declare(strict_types=1);

namespace BC\Modules\Books\Model;

use BC\Core\Trait\WebsiteSettingsTrait;
use BC\Model\Media;
use BC\Modules\Books\Core\Trait\BookFormatProviderTrait;
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
 * @method string getSlug()
 * @method self setSlug(string $slug)
 * @method string getTitle()
 * @method self setTitle(string $title)
 * @method Media|null getCover()
 * @method self setCover(Media|null $cover)
 * @method Media|null getCoverBg()
 * @method self setCoverBg(Media|null $coverBg)
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
 * @method int getPosition()
 * @method self setPosition(int $position)
 */
#[DS\Table('books')]
class Book extends AEntity {
    use BookFormatProviderTrait;
    use WebsiteSettingsTrait;

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
    protected ?Media $coverBg = null;

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

    #[DS\Column]
    protected int $position = 0;

    /**
     * @return BookFormat[]
     *
     * @throws DBException
     * @throws QueryBuilderException
     * @throws ModelException
     */
    public function getFormats(): array {
        $result = [];

        $availableFormats = $this->getBookFormatProvider()->getFormats();
        $storedFormats = BookFormat::find(['book' => $this]);

        foreach ($availableFormats as $format) {
            if (
                !(
                $foundFormat = array_find(
                    $storedFormats,
                    static fn (BookFormat $bFormat): bool => $bFormat->getType() === $format->type
                )
                )
            ) {
                $foundFormat = new BookFormat();
                $foundFormat->setType($format->type)
                            ->setAllowed(false)
                            ->setBook($this)
                            ->setDump('')
                            ->setFilename('')
                            ->setSize(0)
                            ->persist();
            }

            $result[] = $foundFormat;
        }

        return $result;
    }

    /**
     * @throws DBException
     * @throws ModelException
     * @throws QueryBuilderException
     */
    public function setFormat(BookFormat $format): self {
        /** @var BookFormat|null $storedFormat */
        $storedFormat = BookFormat::find([
            'book' => $this,
            'type' => $format->getType()
        ]);

        if ($storedFormat) {
            $storedFormat->setAllowed($format->getAllowed())
                         ->setFilename($format->getFilename())
                         ->setDump($format->getDump())
                         ->setSize($format->getSize())
                         ->persist();
        } else {
            $format->setBook($this)->persist();
        }

        return $this;
    }

    public function remove(): void {
        $this->cover?->remove();
        $this->coverBg?->remove();

        parent::remove();
    }

    /**
     * @throws ModelException
     * @throws DBException
     * @throws QueryBuilderException
     */
    public function bumpLastUpdateDate(): static {
        $this->setLastUpdateDate(
            new DateTime('now')
        );

        $this->persist();

        return $this;
    }

    public function getUrl(): string {
        return $this->getWebsiteSettings()->getWebRoot() . '/books/' . $this->getSlug();
    }

    public function isTranslation(): bool {
        return $this->type === self::TYPE_TRANSLATION;
    }

    public function isAuteur(): bool {
        return $this->type === self::TYPE_AUTEUR;
    }

    public function getAuthors(): array {
        if ($this->isTranslation()) {
            $authors = explode(',', $this->getAuthor());
        } else {
            $authors = ['BaD ClusteR'];
        }

        return array_map(
            static fn (string $author): string => trim($author),
            $authors
        );
    }

    /**
     * @return Chapter[]
     *
     * @throws DBException
     * @throws ModelException
     * @throws QueryBuilderException
     */
    public function getChapters(): array {
        return Chapter::find(['book' => $this], ['position', 'ASC']);
    }
}
