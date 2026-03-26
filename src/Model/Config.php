<?php

namespace BC\Model;

use Runway\DataStorage\Attribute as DS;
use Runway\Model\AEntity;

/**
 * @method int getId
 * @method self setId(int $id)
 * @method string getName
 * @method self setName(string $name)
 * @method string|null getValue
 * @method self setValue(string $value)
 */
#[DS\Table("config")]
class Config extends AEntity
{
    #[DS\Id]
    protected int $id;

    #[DS\Column]
    protected string $name;

    #[DS\Column]
    protected ?string $value;
}