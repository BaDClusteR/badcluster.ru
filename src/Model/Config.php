<?php

namespace BC\Model;

use BC\Core\Trait\LoggerTrait;
use Runway\DataStorage\Attribute as DS;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Exception\Exception;
use Runway\Model\AEntity;
use Runway\Model\Exception\ModelException;

/**
 * @method int getId()
 * @method self setId(int $id)
 * @method string getName()
 * @method self setName(string $name)
 * @method string|null getValue()
 * @method self setValue(string|null $value)
 */
#[DS\Table("config")]
class Config extends AEntity
{
    use LoggerTrait;

    #[DS\Id]
    protected int $id;

    #[DS\Column]
    protected string $name;

    #[DS\Column]
    protected ?string $value;

    public static function getConfig(string $configName): string {
        try {
            return self::findOne([ 'name' => $configName ])?->getValue();
        } catch (Exception $e) {
            self::getLoggerStatic()->warning(
                __METHOD__ . ': Error while trying to find config',
                [
                    'name'          => $configName,
                    'error_code'    => $e->getCode(),
                    'error_message' => $e->getMessage(),
                ]
            );

            return '';
        }
    }
}
