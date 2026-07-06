<?php

namespace BC\Model;

use Runway\DataStorage\Attribute as DS;
use Runway\Model\AEntity;

/**
 * @generated-model-helpers
 * @method int getId()
 * @method self setId(int $id)
 * @method string getPath()
 * @method self setPath(string $path)
 * @method int getCode()
 * @method self setCode(int $code)
 * @method string|null getDestination()
 * @method self setDestination(string|null $destination)
 */

#[DS\Table('redirects')]
class Redirect extends AEntity {
    #[DS\Id]
    protected int $id;

    #[DS\Column]
    protected string $path = '';

    #[DS\Column]
    protected int $code = 0;

    #[DS\Column]
    protected ?string $destination = null;
}
