<?php

declare(strict_types=1);

namespace BC\Api\DTO;

use Runway\DataStorage\QueryBuilder\IQueryBuilder;
use Runway\Model\AEntity;

/**
 * @template T of AEntity
 */
readonly class GetEntitiesListRequest {
    /**
     * @param class-string<T> $entityFQN The entities of the query builder.
     * @param string[]        $columnsToFind
     * @param string[]        $sortableColumns
     */
    public function __construct(
        public string $entityFQN,
        public IQueryBuilder $qb,
        public string $filter,
        public array $columnsToFind,
        public string $sortBy,
        public string $sortDir,
        public int $page,
        public int $perPage,
        public array $sortableColumns
    ) {
    }
}
