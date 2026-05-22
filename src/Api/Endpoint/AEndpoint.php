<?php

namespace BC\Api\Endpoint;

use ApiPlatform\Exception\BadRequestException;
use ApiPlatform\Exception\RuntimeInternalErrorException;
use BC\Exception\UnprocessableEntityException;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;
use Runway\DataStorage\QueryBuilder\IQueryBuilder;
use Runway\Exception\Exception;
use Throwable;

abstract class AEndpoint {
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

    protected function getSortableColumns(): array {
        return [];
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
     * @throws BadRequestException
     */
    protected function setSortLimitAndGetTotal(
        IQueryBuilder $qb,
        string $sortBy,
        string $sortDir,
        int $page,
        int $perPage
    ): int {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));

        if ($sortBy && !in_array($sortBy, $this->getSortableColumns(), true)) {
            throw new BadRequestException(
                sprintf("Не могу сортировать по '%s'.", $sortBy)
            );
        }

        $total = $this->handleWithException(
            static fn () => $qb->count()
        );

        if ($sortBy) {
            $qb->addOrderBy($sortBy, $this->sanitizeSortDirection($sortDir));
        }

        if ($page) {
            $qb->setLimit($perPage, ($page - 1) * $perPage);
        }

        return $total;
    }
}
