<?php

namespace BC\Model;

use Runway\DataStorage\Attribute as DS;
use Runway\Model\AEntity;

/**
 * @method int getId()
 * @method self setId(int $id)
 * @method Post getPost
 * @method self setPost(Post $post)
 * @method Tag getTag
 * @method self setTag(Tag $tag)
 */
#[DS\Table("tags")]
class PostTag extends AEntity
{
    #[DS\Id]
    protected int $id;

    #[DS\Column]
    protected Post $post;

    #[DS\Column]
    protected Tag $tag;
}
