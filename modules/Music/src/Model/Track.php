<?php

declare(strict_types=1);

namespace BC\Modules\Music\Model;

use BC\Core\Trait\WebsiteSettingsTrait;
use Runway\DataStorage\Attribute as DS;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Model\AEntity;
use Runway\Model\Exception\ModelException;

/**
 * @generated-model-helpers
 * @method int getId()
 * @method self setId(int $id)
 * @method Album getAlbum()
 * @method self setAlbum(Album $album)
 * @method string getTitle()
 * @method self setTitle(string $title)
 * @method bool getExplicitLanguage()
 * @method self setExplicitLanguage(bool $explicitLanguage)
 * @method string|null getSourceUrl()
 * @method self setSourceUrl(string|null $sourceUrl)
 * @method string|null getLyrics()
 * @method self setLyrics(string|null $lyrics)
 * @method string|null getClipUrl()
 * @method self setClipUrl(string|null $clipUrl)
 * @method int getPosition()
 * @method self setPosition(int $position)
 * @method string getAnnotation()
 * @method self setAnnotation(string $annotation)
 * @method Song|null getSong()
 */
#[DS\Table('tracks')]
class Track extends AEntity {
    use WebsiteSettingsTrait;

    #[DS\Id]
    protected int $id;

    #[DS\Column]
    protected ?Song $song = null;

    #[DS\Column]
    protected Album $album;

    #[DS\Column]
    protected string $title = '';

    #[DS\Column]
    protected bool $explicitLanguage = false;

    #[DS\Column]
    protected string $sourceUrl = '';

    #[DS\Column]
    protected string $lyrics = '';

    #[DS\Column]
    protected string $clipUrl = '';

    #[DS\Column]
    protected int $position = 0;

    #[DS\Column]
    protected string $annotation = '';

    /**
     * @throws DBException
     * @throws ModelException
     * @throws QueryBuilderException
     */
    public function setSong(?Song $song): static {
        if (
            ($oldFile = $this->getSong())
            && $song?->getId() !== $oldFile->getId()
        ) {
            $oldFile->remove();
        }

        $this->song = $song;
        $this->__isChanged = true;

        return $this;
    }

    public function remove(): void {
        if ($song = $this->getSong()) {
            $song->remove();
        }

        parent::remove();
    }
}
