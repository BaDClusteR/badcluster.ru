<?php

declare(strict_types=1);

namespace BC\Modules\Music\Model;

use BC\Core\Trait\WebsiteSettingsTrait;
use BC\Model\Media;
use BC\Modules\Music\Model\Album as AlbumModel;
use DateTime;
use Runway\DataStorage\Attribute as DS;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Exception\Exception;
use Runway\Model\AEntity;
use Runway\Model\Exception\ModelException;

/**
 * @generated-model-helpers
 * @method int getId()
 * @method self setId(int $id)
 * @method string getTitle()
 * @method self setTitle(string $title)
 * @method Media|null getCover()
 * @method self setCover(Media|null $cover)
 * @method string getGenre()
 * @method self setGenre(string $genre)
 * @method string getType()
 * @method self setType(string $type)
 * @method \DateTime getReleaseDate()
 * @method self setReleaseDate(\DateTime $releaseDate)
 * @method string getAnnotation()
 * @method self setAnnotation(string $annotation)
 * @method string|null getShortAnnotation()
 * @method self setShortAnnotation(string|null $shortAnnotation)
 * @method string|null getMusicBy()
 * @method self setMusicBy(string|null $musicBy)
 * @method string|null getVisualBy()
 * @method self setVisualBy(string|null $visualBy)
 * @method string|null getCoverBy()
 * @method self setCoverBy(string|null $coverBy)
 * @method int getPosition()
 * @method self setPosition(int $position)
 * @method string getSlug()
 * @method self setSlug(string $slug)
 */
#[DS\Table('albums')]
class Album extends AEntity {
    use WebsiteSettingsTrait;

    public const string ALBUM_TYPE_SINGLE = 'S';
    public const string ALBUM_TYPE_DOUBLE_SINGLE = 'D';
    public const string ALBUM_TYPE_EXTENDED_PLAY = 'E';
    public const string ALBUM_TYPE_ALBUM = 'A';

    #[DS\Id]
    protected int $id;

    #[DS\Column]
    protected string $title = '';

    #[DS\Column]
    protected ?Media $cover = null;

    #[DS\Column]
    protected string $genre = '';

    #[DS\Column]
    protected string $type = self::ALBUM_TYPE_EXTENDED_PLAY;

    #[DS\Column]
    protected DateTime $releaseDate;

    #[DS\Column]
    protected string $annotation;

    #[DS\Column]
    protected ?string $shortAnnotation = null;

    #[DS\Column]
    protected ?string $musicBy = null;

    #[DS\Column]
    protected ?string $visualBy = null;

    #[DS\Column]
    protected ?string $coverBy = null;

    #[DS\Column]
    protected int $position = 0;

    #[DS\Column]
    protected string $slug = '';

    public function getTypeHumanReadable(): string {
        return match ($this->getType()) {
            self::ALBUM_TYPE_SINGLE => 'Сингл',
            self::ALBUM_TYPE_DOUBLE_SINGLE => 'Двойной сингл',
            self::ALBUM_TYPE_EXTENDED_PLAY => 'EP',
            self::ALBUM_TYPE_ALBUM => 'Альбом',
            default => ''
        };
    }

    /**
     * @return Track[]
     * @throws DBException
     * @throws QueryBuilderException
     *
     * @throws ModelException
     */
    public function getTracks(): array {
        return Track::find(
            ['album' => $this->id],
            ['position', 'ASC']
        );
    }

    /**
     * @throws DBException
     * @throws ModelException
     * @throws QueryBuilderException
     */
    public function hasExplicitLanguage(): bool {
        return array_any(
            $this->getTracks(),
            static fn ($track) => $track->getExplicitLanguage()
        );
    }

    public function getUrl(): string {
        return $this->getWebsiteSettings()->getWebRoot() . '/music/' . $this->getSlug();
    }

    /**
     * @return string[]
     */
    public function getGenres(): array {
        return array_map(
            static fn (string $genre): string => trim($genre),
            explode(',', $this->getGenre())
        );
    }

    public function getTypeAndTracks(): string {
        $type = $this->getType();
        $typeHumanReadable = $this->getTypeHumanReadable();

        if (in_array($type, [AlbumModel::ALBUM_TYPE_SINGLE, AlbumModel::ALBUM_TYPE_DOUBLE_SINGLE], true)) {
            return $typeHumanReadable;
        }
        try {
            $tracksCount = count($this->getTracks());
        } catch (Exception) {
            return '';
        }

        return sprintf('%s • %s %s', $typeHumanReadable, $tracksCount, $this->getTrackWordForm($tracksCount));
    }

    protected function getTrackWordForm(int $tracksCount): string {
        $tracksMod100 = $tracksCount % 100;
        if ($tracksMod100 >= 10 && $tracksMod100 <= 20) {
            return 'треков';
        }

        $tracksMod10 = $tracksCount % 10;

        return match ($tracksMod10) {
            1 => 'трек',
            2, 3, 4 => 'трека',
            default => 'треков'
        };
    }
}
