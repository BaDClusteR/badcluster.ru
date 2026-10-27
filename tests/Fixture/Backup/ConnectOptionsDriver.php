<?php

declare(strict_types=1);

namespace BC\Tests\Fixture\Backup;

use LogicException;
use Runway\DataStorage\DTO\DataStorageConnectionOptionsDTO;
use Runway\DataStorage\IDataStorageDriver;

/**
 * A driver that knows its connection options only: it never connects.
 */
final readonly class ConnectOptionsDriver implements IDataStorageDriver {
    public function __construct(
        private DataStorageConnectionOptionsDTO $options
    ) {}

    public function connect(DataStorageConnectionOptionsDTO $options): mixed {
        throw new LogicException('Not expected in the test.');
    }

    public function getResult(string $query, mixed $vars = []): array {
        throw new LogicException('Not expected in the test.');
    }

    public function getResultsIterator(string $query, mixed $vars = []): iterable {
        throw new LogicException('Not expected in the test.');
    }

    public function execute(string $query, mixed $vars = []): void {
        throw new LogicException('Not expected in the test.');
    }

    public function getFirstResult(string $query, mixed $vars = []): array {
        throw new LogicException('Not expected in the test.');
    }

    public function getColumn(string $query, mixed $vars = [], int|string $columnName = ""): array {
        throw new LogicException('Not expected in the test.');
    }

    public function getFirstScalarResult(string $query, mixed $vars = []): mixed {
        throw new LogicException('Not expected in the test.');
    }

    public function getLastInsertId(): string {
        throw new LogicException('Not expected in the test.');
    }

    public function getConnectOptions(): DataStorageConnectionOptionsDTO {
        return $this->options;
    }
}
