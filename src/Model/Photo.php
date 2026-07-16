<?php

namespace BC\Model;

use DateTime;
use Runway\DataStorage\Attribute as DS;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Model\Exception\ModelException;

/**
 * @generated-model-helpers
 * @method int getId()
 * @method self setId(int $id)
 * @method string getPath()
 * @method self setPath(string $path)
 * @method int getWidth()
 * @method self setWidth(int $width)
 * @method int getHeight()
 * @method self setHeight(int $height)
 * @method int getSize()
 * @method self setSize(int $size)
 * @method string getMime()
 * @method self setMime(string $mime)
 * @method string getAlt()
 * @method self setAlt(string $alt)
 * @method Media|null getParent()
 * @method self setParent(Media|null $parent)
 * @method string getMd5()
 * @method self setMd5(string $md5)
 * @method int getPosition()
 * @method self setPosition(int $position)
 * @method DateTime getUploadedAt()
 * @method self setUploadedAt(DateTime $uploadedAt)
 * @method PhotoPhotoTag[] getPhotoTags()
 */
#[DS\Table('photos')]
class Photo extends Media {
    #[DS\Column]
    protected int $position = 0;

    #[DS\Column]
    protected DateTime $uploadedAt;

    #[DS\Reference(refModel: PhotoPhotoTag::class, refProp: 'photo')]
    protected ?array $photoTags = null;

    public static function getSubfolder(): string {
        return 'photos';
    }

    /**
     * @return PhotoTag[]
     */
    public function getTags(): array {
        return array_map(
            static fn (PhotoPhotoTag $ppt): PhotoTag => $ppt->getTag(),
            $this->getPhotoTags()
        );
    }

    /**
     * @param PhotoTag[] $tags
     *
     * @throws DBException
     * @throws QueryBuilderException
     * @throws ModelException
     */
    public function syncTags(array $tags): static {
        $tagIds = array_map(
            static fn (PhotoTag $t): int => $t->getId(),
            $tags
        );

        $qb = PhotoPhotoTag::getQueryBuilder();
        $qb->delete()
           ->where('photo_id = :photoId')
           ->setVariable('photoId', $this->id);

        if (!empty($tagIds)) {
            // Список int'ов вставляем прямо в SQL: :tagIds забиндил бы весь
            // массив как одну строку ("1, 2, 3"), и NOT IN не сработал бы
            $qb->andWhere('tag_id NOT IN (' . implode(', ', array_map('intval', $tagIds)) . ')');
        }

        $qb->execute();

        $qb = PhotoPhotoTag::getQueryBuilder()
                           ->where('photo_id = :photoId')
                           ->setVariable('photoId', $this->id);

        if (!empty($tagIds)) {
            $qb->andWhere(
                $qb->expr()->in('tag_id', $tagIds)
            );
        }

        $existingTagIds = array_map(
            static fn (PhotoPhotoTag $ppt): int => $ppt->getTag()->getId(),
            $qb->getEntities()
        );

        foreach ($tagIds as $tagId) {
            if (!in_array($tagId, $existingTagIds, true)) {
                $tag = PhotoTag::findByUniqueIdentifier($tagId);
                if ($tag) {
                    new PhotoPhotoTag()
                        ->setTag($tag)
                        ->setPhoto($this)
                        ->persist();
                }
            }
        }

        return $this;
    }

    protected function init(): void {
        parent::init();

        // Дефолт нужен строкам тамбнейлов, которые создает ThumbnailGenerator:
        // он не знает про uploadedAt и без дефолта в базу уедет NULL
        $this->uploadedAt ??= new DateTime();
    }
}
