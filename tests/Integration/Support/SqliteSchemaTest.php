<?php

declare(strict_types=1);

namespace BC\Tests\Integration\Support;

use BC\Model\Config;
use BC\Model\Fact;
use BC\Tests\Support\SqliteSchema;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * The SQLite tables of the models work with the models of Runway.
 */
#[CoversNothing]
final class SqliteSchemaTest extends TestCase {
    public function testModelTableStoresEntities(): void {
        SqliteSchema::createModelTables(Fact::class);

        $fact = new Fact()->setTitle('Title')->setContent('Content');
        $fact->persist();

        $found = Fact::findByUniqueIdentifier($fact->getId());

        self::assertNotNull($found);
        self::assertSame('Title', $found->getTitle());
        self::assertSame('Content', $found->getContent());
    }

    public function testTablesAreRecreatedEmpty(): void {
        SqliteSchema::createModelTables(Config::class);
        Config::setConfig('name', 'value');
        self::assertSame('value', Config::getConfig('name'));

        SqliteSchema::createModelTables(Config::class);

        self::assertSame('', Config::getConfig('name'));
    }

    public function testColumnsOfModel(): void {
        self::assertSame(
            '`id` INTEGER PRIMARY KEY AUTOINCREMENT, `title` TEXT, `content` TEXT',
            SqliteSchema::getModelColumns(Fact::class)
        );
        self::assertSame('facts', SqliteSchema::getModelTable(Fact::class));
    }

    public function testAuthTables(): void {
        $driver = SqliteSchema::createAuthTables();

        self::assertEquals(0, $driver->getFirstScalarResult('SELECT COUNT(*) FROM `{options}`'));
        self::assertEquals(0, $driver->getFirstScalarResult('SELECT COUNT(*) FROM `{tokens}`'));
        self::assertEquals(0, $driver->getFirstScalarResult('SELECT COUNT(*) FROM `{login_attempts}`'));
    }
}
