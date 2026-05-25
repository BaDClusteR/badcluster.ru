<?php

declare(strict_types=1);

namespace BC\Api\DTO;

use Runway\DataStorage\QueryBuilder\IQueryBuilder;

readonly class GetEntitiesListRequest {
    /**
     * @param string[] $columnsToFind
     * @param string[] $sortableColumns
     */
    public function __construct(
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
