<?php

declare(strict_types=1);

namespace BC\Tests\Fixture\Api;

use BC\Api\DTO\GetEntitiesListRequest;
use BC\Api\DTO\ListResponseDTO;
use BC\Api\Endpoint\AEndpoint;
use BC\Model\Media;
use Runway\DataStorage\QueryBuilder\IQueryBuilder;
use Runway\Model\AEntity;

/**
 * The helpers of AEndpoint made public.
 */
final class TestEndpoint extends AEndpoint {
    /**
     * @template T
     *
     * @param callable(): T $handler
     *
     * @return T
     */
    public function callHandleWithException(callable $handler): mixed {
        return $this->handleWithException($handler);
    }

    public function callHandleActionWithException(callable $handler, string $title): void {
        $this->handleActionWithException($handler, $title);
    }

    public function callSanitizeSortDirection(string $direction): string {
        return $this->sanitizeSortDirection($direction);
    }

    /**
     * @param string[] $columns
     */
    public function callAddFilter(IQueryBuilder $qb, string $filter, array $columns): IQueryBuilder {
        return $this->addFilter($qb, $filter, $columns);
    }

    /**
     * @param string[] $sortableColumns
     */
    public function callSetSortLimitAndGetTotal(
        IQueryBuilder $qb,
        string        $sortBy,
        string        $sortDir,
        int           $page,
        int           $perPage,
        array         $sortableColumns
    ): int {
        return $this->setSortLimitAndGetTotal($qb, $sortBy, $sortDir, $page, $perPage, $sortableColumns);
    }

    /**
     * @param class-string<AEntity> $entityFQN
     * @param array<int|string>     $ids The IDs of a request body: anything, the endpoint casts them.
     */
    public function callDeleteEntities(string $entityFQN, array $ids): void {
        // @phpstan-ignore argument.type (the tests check that a string ID cannot inject SQL)
        $this->deleteEntities($entityFQN, $ids);
    }

    /**
     * @param callable(AEntity): mixed $build
     *
     * @return ListResponseDTO<mixed>
     */
    public function callGetEntitiesList(GetEntitiesListRequest $request, callable $build): ListResponseDTO {
        return $this->getEntitiesList($request, $build);
    }

    /**
     * @param class-string<AEntity>  $entityFQN
     * @param callable(AEntity): mixed $build
     */
    public function callGetEntity(string $entityFQN, int $id, string $notFoundMessage, callable $build): mixed {
        return $this->getEntity($entityFQN, $id, $notFoundMessage, $build);
    }

    /**
     * @param array<mixed>|null $mediaInfo
     */
    public function callFindMedia(?array $mediaInfo): ?Media {
        return $this->findMedia($mediaInfo);
    }

    /**
     * @template T of AEntity
     *
     * @param class-string<T> $modelFQN
     *
     * @return T|null
     */
    public function callGetEntityBySlug(string $modelFQN, string $slug, ?int $id = null, ?IQueryBuilder $qb = null): ?AEntity {
        return $this->getEntityBySlug($modelFQN, $slug, $id, $qb);
    }
}
