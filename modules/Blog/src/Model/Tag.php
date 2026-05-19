<?php

namespace BC\Modules\Blog\Model;

use Runway\DataStorage\Attribute as DS;
use Runway\Model\AEntity;

/**
 * @method int getId()
 * @method self setId(int $id)
 * @method string getTitle()
 * @method self setTitle(string $title)
 * @method string getSlug()
 * @method self setSlug(string $slug)
 */
#[DS\Table('tags')]
class Tag extends AEntity {
    #[DS\Id]
    protected int $id;

    #[DS\Column]
    protected string $title = '';

    #[DS\Column]
    protected string $slug = '';
}
