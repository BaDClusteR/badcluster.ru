<?php

namespace BC\Model;

use Runway\DataStorage\Attribute as DS;
use Runway\Model\AEntity;

/**
 * @generated-model-helpers
 * @method int getId()
 * @method self setId(int $id)
 * @method string getContent()
 * @method self setContent(string $content)
 */
#[DS\Table('facts')]
class Fact extends AEntity {
    #[DS\Id]
    protected int $id;

    #[DS\Column]
    protected string $content = '';
}
