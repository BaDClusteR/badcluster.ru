<?php

declare(strict_types=1);

namespace BC\Api\Endpoint;

use ApiPlatform\Exception\BadRequestException;
use ApiPlatform\Exception\RuntimeInternalErrorException;
use BC\Api\DTO\GetEntitiesListRequest;
use BC\Api\DTO\ListResponseDTO;
use BC\Api\Exception\NotFoundException;
use BC\Exception\UnprocessableEntityException;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;
use Runway\DataStorage\QueryBuilder\IQueryBuilder;
use Runway\Exception\Exception;
use Runway\Model\AEntity;
use Throwable;

abstract class AEndpoint {
    public const int PER_PAGE_DEFAULT = 25;

    /**
     * @template T
     * @param callable(): T $handler
     *
     * @return T
     */
    protected function handleWithException(callable $handler): mixed {
        try {
            return $handler();
        } catch (Throwable $e) {
            throw new RuntimeInternalErrorException($e->getMessage(), $e);
        }
    }

    /**
     * @throws UnprocessableEntityException
     */
    protected function handleActionWithException(callable $handler, string $unprocessableErrorTitle): void {
        try {
            $handler();
        } catch (ActionValidationException $e) {
            throw new UnprocessableEntityException(
                $e->getErrors(),
                $unprocessableErrorTitle
            );
        } catch (Exception $e) {
            throw new RuntimeInternalErrorException($e->getMessage(), $e);
        }
    }

    protected function sanitizeSortDirection(string $sortDirection): string {
        $sortDirection = strtoupper(trim($sortDirection));

        return in_array($sortDirection, ['ASC', 'DESC'], true)
            ? $sortDirection
            : $this->getDefaultSortDirection();
    }

    protected function getDefaultSortDirection(): string {
        return 'ASC';
    }

    /**
     * @param string[] $columnsToFind
     */
    protected function addFilter(IQueryBuilder $qb, string $filter, array $columnsToFind): IQueryBuilder {
        $filter = strtolower(trim($filter));

        if ($filter) {
            $filterParts = array_map(
                static fn (string $column): string => "LOWER($column) LIKE :filter",
                $columnsToFind
            );

            $qb->andWhere(implode(' OR ', $filterParts))
                ->setVariable('filter', "%$filter%");
        }

        return $qb;
    }

    /**
     * @param string[] $sortableColumns
     *
     * @throws BadRequestException
     */
    protected function setSortLimitAndGetTotal(
        IQueryBuilder $qb,
        string $sortBy,
        string $sortDir,
        int $page,
        int $perPage,
        array $sortableColumns
    ): int {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));

        if ($sortBy && !in_array($sortBy, $sortableColumns, true)) {
            throw new BadRequestException(
                sprintf("Не могу сортировать по '%s'.", $sortBy)
            );
        }

        $total = $this->handleWithException(
            static fn () => $qb->count()
        );

        if ($sortBy) {
            $qb->clearOrderBy()->addOrderBy($sortBy, $this->sanitizeSortDirection($sortDir));
        }

        if ($page) {
            $qb->setLimit($perPage, ($page - 1) * $perPage);
        }

        return $total;
    }

    /**
     * @param class-string<AEntity> $entityFQN
     * @param int[] $entityIds
     */
    protected function deleteEntities(string $entityFQN, array $entityIds): void {
        $qb = $entityFQN::getQueryBuilder();

        $this->handleWithException(
            static fn () => $qb->delete()->where(
                $qb->expr()->in('id', $entityIds)
            )->execute()
        );
    }

    /**
     * @param callable(AEntity $entity): mixed $buildListResponseItem
     *
     * @throws BadRequestException
     */
    protected function getEntitiesList(
        GetEntitiesListRequest $request,
        callable $buildListResponseItem
    ): ListResponseDTO {
        $qb = $request->qb;

        $this->addFilter($qb, $request->filter, $request->columnsToFind);
        $total = $this->setSortLimitAndGetTotal(
            $qb,
            $request->sortBy,
            $request->sortDir,
            $request->page,
            $request->perPage,
            $request->sortableColumns
        );

        return $this->handleWithException(
            fn () => new ListResponseDTO(
                items: array_map(
                    static fn (AEntity $entity) => $buildListResponseItem($entity),
                    $qb->getEntities()
                ),
                total: $total
            )
        );
    }

    /**
     * @param class-string<AEntity>           $entityFQN
     * @param callable(AEntity $model): mixed $buildData
     *
     * @throws NotFoundException
     */
    protected function getEntity(
        string $entityFQN,
        int $id,
        string $notFoundMessage,
        callable $buildData
    ): mixed {
        /** @var AEntity|null $entity */
        $entity = $this->handleWithException(
            static fn () => $entityFQN::findByUniqueIdentifier($id)
        );

        if (!$entity) {
            throw new NotFoundException(
                str_replace('{{id}}', $id, $notFoundMessage)
            );
        }

        return $this->handleWithException(
            fn () => $buildData($entity)
        );
    }
}
