<?php

declare(strict_types=1);

namespace BC\Model;

use Runway\DataStorage\Attribute as DS;
use Runway\Model\AEntity;

/**
 * @generated-model-helpers
 * @method int getId()
 * @method self setId(int $id)
 * @method Photo getPhoto()
 * @method self setPhoto(Photo $photo)
 * @method PhotoTag getTag()
 * @method self setTag(PhotoTag $tag)
 */
#[DS\Table('photo_photo_tags')]
class PhotoPhotoTag extends AEntity {
    #[DS\Id]
    protected int $id;

    #[DS\Column]
    protected Photo $photo;

    #[DS\Column]
    protected PhotoTag $tag;
}
