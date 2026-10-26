<?php

declare(strict_types=1);

namespace BC\Core\Helper;

use Runway\Model\AEntity;
use UnexpectedValueException;

/**
 * Типизация сущностей query builder'а: getEntities() / iterateEntities() отдают AEntity, хотя создают сущности
 * класса из from() (AEntity::getQueryBuilder() — класса модели). Проверяет каждую сущность и отдаёт её с типом модели.
 */
final class EntityList {
    /**
     * @template T of AEntity
     * @param class-string<T> $entityFQN
     * @param array<AEntity>  $entities
     *
     * @return list<T>
     *
     * @throws UnexpectedValueException An entity of another class.
     */
    public static function of(string $entityFQN, array $entities): array {
        return array_map(
            static fn (AEntity $entity): AEntity => self::check($entityFQN, $entity),
            array_values($entities)
        );
    }

    /**
     * @template T of AEntity
     * @param class-string<T>   $entityFQN
     * @param iterable<AEntity> $entities
     *
     * @return iterable<T>
     *
     * @throws UnexpectedValueException An entity of another class.
     */
    public static function iterate(string $entityFQN, iterable $entities): iterable {
        foreach ($entities as $entity) {
            yield self::check($entityFQN, $entity);
        }
    }

    /**
     * @template T of AEntity
     * @param class-string<T> $entityFQN
     *
     * @return T
     *
     * @throws UnexpectedValueException
     */
    public static function check(string $entityFQN, AEntity $entity): AEntity {
        if (!($entity instanceof $entityFQN)) {
            throw new UnexpectedValueException(
                sprintf('%s is not %s', $entity::class, $entityFQN)
            );
        }

        return $entity;
    }
}
