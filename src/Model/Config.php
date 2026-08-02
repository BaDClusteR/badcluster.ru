<?php

declare(strict_types=1);

namespace BC\Model;

use BC\Core\Trait\LoggerTrait;
use Runway\DataStorage\Attribute as DS;
use Runway\Exception\Exception;
use Runway\Model\AEntity;

/**
 * @generated-model-helpers
 * @method int getId()
 * @method self setId(int $id)
 * @method string getName()
 * @method self setName(string $name)
 * @method string|null getValue()
 * @method self setValue(string|null $value)
 */
#[DS\Table('config')]
class Config extends AEntity {
    use LoggerTrait;

    #[DS\Id]
    protected int $id;

    #[DS\Column]
    protected string $name;

    #[DS\Column]
    protected ?string $value;

    public static function getConfig(string $configName): string {
        try {
            // ?? '' обязателен: у несуществующей настройки findOne вернёт null,
            // и без него метод падал бы на несоответствии типу возврата
            return self::findOne(['name' => $configName])?->getValue() ?? '';
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

    /**
     * Создаёт настройку, если её ещё нет, иначе обновляет значение.
     *
     * @throws Exception
     */
    public static function setConfig(string $configName, ?string $value): void {
        $config = self::findOne(['name' => $configName])
            ?? (new self())->setName($configName);

        $config->setValue($value)
               ->persist();
    }
}
