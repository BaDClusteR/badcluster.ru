<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Model;

use Runway\DataStorage\Attribute as DS;
use Runway\Model\AEntity;

/**
 * @method int getId()
 * @method self setId(int $id)
 * @method \BC\Modules\Blog\Model\Post getPost()
 * @method self setPost(\BC\Modules\Blog\Model\Post $post)
 * @method \BC\Modules\Blog\Model\Tag getTag()
 * @method self setTag(\BC\Modules\Blog\Model\Tag $tag)
 */
#[DS\Table('post_tags')]
class PostTag extends AEntity {
    #[DS\Id]
    protected int $id;

    #[DS\Column]
    protected Post $post;

    #[DS\Column]
    protected Tag $tag;
}
