<?php

declare(strict_types=1);

namespace BC\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Runway\DataStorage\IDataStorageDriver;
use Runway\Singleton\Container;

/**
 * The container of the site boots with the test env: the database is SQLite in memory.
 */
final class ContainerTest extends TestCase {
    public function testDatabaseIsSqliteInMemory(): void {
        $driver = Container::getInstance()->getDataStorageDriver();

        self::assertInstanceOf(IDataStorageDriver::class, $driver);
        self::assertSame('1', (string)$driver->getFirstScalarResult('SELECT sqlite_version() IS NOT NULL'));
    }
}
