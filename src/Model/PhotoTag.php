<?php

declare(strict_types=1);

namespace BC\Model;

use Runway\DataStorage\Attribute as DS;
use Runway\Model\AEntity;

/**
 * @generated-model-helpers
 * @method int getId()
 * @method self setId(int $id)
 * @method string getTitle()
 * @method self setTitle(string $title)
 * @method int getPosition()
 * @method self setPosition(int $position)
 */
#[DS\Table('photo_tags')]
class PhotoTag extends AEntity {
    #[DS\Id]
    protected int $id;

    #[DS\Column]
    protected string $title = '';

    #[DS\Column]
    protected int $position = 0;
}
