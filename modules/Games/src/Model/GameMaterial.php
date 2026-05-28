<?php

declare(strict_types=1);

namespace BC\Modules\Games\Model;

use BC\Core\Trait\WebsiteSettingsTrait;
use BC\Model\Media;
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
 * @method string getTitle()
 * @method self setTitle(string $title)
 * @method string getSlug()
 * @method self setSlug(string $slug)
 * @method string getShortTitle()
 * @method self setShortTitle(string $shortTitle)
 * @method Game getGame()
 * @method self setGame(Game $game)
 * @method DateTime getDateAdded()
 * @method self setDateAdded(DateTime $dateAdded)
 * @method string getAnnotation()
 * @method self setAnnotation(string $annotation)
 * @method array getDescription()
 * @method self setDescription(array $description)
 * @method array getSetupInstructions()
 * @method self setSetupInstructions(array $setupInstructions)
 * @method GameMaterialFile|null getFile()
 * @method string getType()
 * @method self setType(string $type)
 * @method string getUrl()
 * @method self setUrl(string $url)
 */
#[DS\Table('game_materials')]
class GameMaterial extends AEntity {
    use WebsiteSettingsTrait;

    public const string TYPE_FILE = 'F';
    public const string TYPE_ARTICLE = 'A';

    #[DS\Id]
    protected int $id;

    #[DS\Column]
    protected string $title = '';

    #[DS\Column]
    protected string $slug = '';

    #[DS\Column]
    protected string $shortTitle = '';

    #[DS\Column]
    protected Game $game;

    #[DS\Column]
    protected DateTime $dateAdded;

    #[DS\Column]
    protected string $annotation = '';

    #[DS\Column]
    protected array $description = [];

    #[DS\Column]
    protected array $setupInstructions = [];

    #[DS\Column]
    protected ?GameMaterialFile $file = null;

    #[DS\Column]
    protected string $type = self::TYPE_FILE;

    #[DS\Column]
    protected string $url = '';

    /**
     * @throws DBException
     * @throws ModelException
     * @throws QueryBuilderException
     */
    public function setFile(?GameMaterialFile $file): static {
        if (
            ($oldFile = $this->getFile())
            && $file?->getId() !== $oldFile->getId()
        ) {
            $oldFile->remove();
        }

        $this->file = $file;
        $this->__isChanged = true;

        return $this;
    }

    public function remove(): void {
        if ($file = $this->getFile()) {
            $file->remove();
        }

        parent::remove();
    }

    public function getMaterialUrl(): string {
        return $this->isFile()
            ? $this->getWebsiteSettings()->getWebRoot() . '/games/' . $this->game->getSlug() . '/' . $this->slug
            : $this->getWebsiteSettings()->getWebRoot() . $this->url;
    }

    public function isFile(): bool {
        return $this->type === self::TYPE_FILE;
    }

    public function isArticle(): bool {
        return $this->type === self::TYPE_ARTICLE;
    }
}
