<?php

declare(strict_types=1);

namespace BC\Tests\Support;

use LogicException;
use Runway\DataStorage\IDataStorageDriver;
use Runway\Model\AEntity;
use Runway\Model\Provider\IDataStoragePropertiesProvider;
use Runway\Singleton\Container;

/**
 * SQLite versions of the tables of the site (the real schema is MySQL: schema.sql, modules/*\/schema.sql, the models)
 * in the database of the container: SQLite in memory (see tests/config/test.env). Every call recreates the tables, so
 * a test starts with empty ones.
 */
final class SqliteSchema {
    /**
     * The tables of API platform: the credentials, the sessions, the failed logins (see ApiPlatform\Core\Auth\AuthStorage).
     *
     * @var array<string, string> Table (without the prefix) => columns.
     */
    public const array AUTH_TABLES = [
        'options'        => 'id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, value TEXT NOT NULL',
        'tokens'         => 'id INTEGER PRIMARY KEY AUTOINCREMENT, token TEXT NOT NULL, generated INTEGER NOT NULL,'
            . ' valid_until INTEGER NOT NULL, last_active INTEGER NOT NULL, ip TEXT NOT NULL, user_agent TEXT NOT NULL,'
            . ' server_info TEXT NOT NULL, expired INTEGER NOT NULL, active INTEGER NOT NULL',
        'login_attempts' => 'id INTEGER PRIMARY KEY AUTOINCREMENT, ip TEXT NOT NULL, attempted INTEGER NOT NULL',
    ];

    /**
     * The driver of the container; it is checked to be SQLite.
     */
    public static function getDriver(): IDataStorageDriver {
        $driver = Container::getInstance()->getDataStorageDriver();
        TestEnvironment::assertTestDatabase($driver->getConnectOptions()->dsn);

        return $driver;
    }

    /**
     * Empty tables of the models: the columns of the #[DS\Id] and #[DS\Column] properties, the types by the data
     * storage types of the properties (int and bool are INTEGER, float is REAL, the rest is TEXT).
     *
     * @param class-string<AEntity> ...$models
     */
    public static function createModelTables(string ...$models): IDataStorageDriver {
        $driver = self::getDriver();

        foreach ($models as $model) {
            self::createRawTable(self::getModelTable($model), self::getModelColumns($model), $driver);
        }

        return $driver;
    }

    /**
     * The SQL of the columns of a model table.
     *
     * @param class-string<AEntity> $model
     */
    public static function getModelColumns(string $model): string {
        $provider = Container::getInstance()->getService(IDataStoragePropertiesProvider::class);
        $columns = [];

        foreach ($provider->getDataStorageProperties($model) as $property) {
            $column = $property->getColumn();

            $columns[$column] = $property->isPrimary()
                ? "`$column` INTEGER PRIMARY KEY AUTOINCREMENT"
                : "`$column` " . match ($property->getDataStorageType()) {
                    'int', 'bool' => 'INTEGER',
                    'float'       => 'REAL',
                    default       => 'TEXT',
                };
        }

        if ($columns === []) {
            throw new LogicException("$model has no columns.");
        }

        return implode(', ', $columns);
    }

    /**
     * @param class-string<AEntity> $model
     *
     * @return string The table name without the prefix.
     */
    public static function getModelTable(string $model): string {
        $table = Container::getInstance()->getService(IDataStoragePropertiesProvider::class)->getTableName($model);

        if ($table === '') {
            throw new LogicException("$model has no table.");
        }

        return $table;
    }

    /**
     * Empty tables of all the models of the site and of the modules of the tests (see MODULES of
     * tests/config/test.env), and the ones of API platform: everything a page of the site may read.
     */
    public static function createSiteTables(): IDataStorageDriver {
        self::createModelTables(...self::getSiteModels());

        return self::createAuthTables();
    }

    /**
     * @return list<class-string<AEntity>> The models of src/Model and modules/<Module>/src/Model with a table.
     */
    public static function getSiteModels(): array {
        $models = [];
        $folders = ['BC\\Model\\' => PROJECT_ROOT . '/src/Model'];

        foreach (['Blog', 'Books', 'Games', 'Music', 'Backup'] as $module) {
            $folders["BC\\Modules\\$module\\Model\\"] = PROJECT_ROOT . "/modules/$module/src/Model";
        }

        foreach ($folders as $namespace => $folder) {
            foreach (glob("$folder/*.php") ?: [] as $file) {
                $class = $namespace . basename($file, '.php');

                if (
                    class_exists($class)
                    && is_subclass_of($class, AEntity::class)
                    && !new \ReflectionClass($class)->isAbstract()
                    && new \ReflectionClass($class)->getAttributes(\Runway\DataStorage\Attribute\Table::class) !== []
                ) {
                    $models[] = $class;
                }
            }
        }

        return $models;
    }

    /**
     * Empty tables of API platform (see AUTH_TABLES).
     */
    public static function createAuthTables(): IDataStorageDriver {
        $driver = self::getDriver();

        foreach (self::AUTH_TABLES as $table => $columns) {
            self::createRawTable($table, $columns, $driver);
        }

        return $driver;
    }

    /**
     * An empty table by its name without the prefix.
     */
    public static function createRawTable(string $table, string $columns, ?IDataStorageDriver $driver = null): void {
        $driver ??= self::getDriver();
        $driver->execute("DROP TABLE IF EXISTS `{{$table}}`");
        $driver->execute("CREATE TABLE `{{$table}}` ($columns)");
    }
}
