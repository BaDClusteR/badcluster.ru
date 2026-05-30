<?php

declare(strict_types=1);

namespace BC\Modules\Games\Model;

use BC\Core\Trait\WebsiteSettingsTrait;
use BC\Model\Media;
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
 * @method int|null getReleaseYear()
 * @method self setReleaseYear(int|null $releaseYear)
 * @method Media|null getCover()
 */
#[DS\Table('games')]
class Game extends AEntity {
    use WebsiteSettingsTrait;

    #[DS\Id]
    protected int $id;

    #[DS\Column]
    protected string $title = '';

    #[DS\Column]
    protected string $slug = '';

    #[DS\Column]
    protected ?int $releaseYear = null;

    #[DS\Column]
    protected ?Media $cover = null;

    /**
     * @throws ModelException
     * @throws DBException
     * @throws QueryBuilderException
     */
    public function setCover(?Media $cover): static {
        if ($this->cover && $this->cover->getId() !== $cover?->getId()) {
            $this->cover->remove();
        }

        $this->cover = $cover;
        $this->__isChanged = true;

        return $this;
    }

    public function remove(): void {
        $this->getCover()?->remove();

        foreach (GameMaterial::find(['game_id' => $this->id]) as $material) {
            $material->remove();
        }

        parent::remove();
    }
}
