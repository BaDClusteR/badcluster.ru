<?php

declare(strict_types=1);

namespace BC\Tests\Support;

use ApiPlatform\Core\Singleton\IAuth;
use PDO;
use RuntimeException;
use Runway\Singleton\Container;

/**
 * The site in the PHP built-in server (the router script tests/E2E/server.php, the document root is the project) with
 * its own environment and database in a temporary folder: an SQLite file with the tables of the site, never the
 * database of the developer.
 */
final class HttpServer {
    public const string TABLE_PREFIX = 'bc_';

    /** @var resource|null */
    private $process = null;

    private readonly string $root;

    private int $port = 0;

    private ?PDO $database = null;

    public function __construct() {
        $this->root = TempDir::create('bc-e2e-');
    }

    /**
     * @param string $login    The credentials of the admin area.
     * @param string $password
     */
    public function start(string $login, string $password): void {
        $this->port = $this->getFreePort();
        $this->createDatabase();
        $this->setCredentials($login, $password);
        $this->writeEnv();

        $process = proc_open(
            [PHP_BINARY, '-S', "127.0.0.1:$this->port", '-t', PROJECT_ROOT, PROJECT_ROOT . '/tests/E2E/server.php'],
            [
                0 => ['pipe', 'r'],
                1 => ['file', "$this->root/server.log", 'a'],
                2 => ['file', "$this->root/server.log", 'a'],
            ],
            $pipes,
            $this->root,
            [...getenv(), 'BC_E2E_ENV_FILE' => "$this->root/e2e.env"]
        );

        if ($process === false) {
            throw new RuntimeException('Cannot start the PHP built-in server.');
        }

        $this->process = $process;
        $this->waitUntilReady();
    }

    public function stop(): void {
        if ($this->process !== null) {
            proc_terminate($this->process);
            proc_close($this->process);
            $this->process = null;
        }

        $this->database = null;
        TempDir::remove($this->root);
    }

    public function getBaseUrl(): string {
        return "http://127.0.0.1:$this->port";
    }

    public function getDatabase(): PDO {
        return $this->database ??= new PDO(
            'sqlite:' . $this->getDatabasePath(),
            null,
            null,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    /**
     * @return string The log of the server (e.g. for a failed test).
     */
    public function getLog(): string {
        return (string)@file_get_contents("$this->root/server.log");
    }

    private function getDatabasePath(): string {
        return "$this->root/e2e.sqlite";
    }

    /**
     * The tables of all the models and of API platform (see SqliteSchema).
     */
    private function createDatabase(): void {
        $database = $this->getDatabase();

        foreach (SqliteSchema::getSiteModels() as $model) {
            $database->exec(
                'CREATE TABLE `' . self::TABLE_PREFIX . SqliteSchema::getModelTable($model) . '` ('
                . SqliteSchema::getModelColumns($model) . ')'
            );
        }

        foreach (SqliteSchema::AUTH_TABLES as $table => $columns) {
            $database->exec('CREATE TABLE `' . self::TABLE_PREFIX . "$table` ($columns)");
        }
    }

    /**
     * The credentials are set by API platform (as the console command does) in the database of the tests, then copied.
     */
    private function setCredentials(string $login, string $password): void {
        $driver = SqliteSchema::createAuthTables();
        Container::getInstance()->getService(IAuth::class)->setCredentials($login, $password);

        $insert = $this->getDatabase()->prepare('INSERT INTO `' . self::TABLE_PREFIX . 'options` (name, value) VALUES (:name, :value)');

        foreach ($driver->getResult('SELECT name, value FROM `{options}`') as $row) {
            $insert->execute(['name' => $row['name'], 'value' => $row['value']]);
        }
    }

    private function writeEnv(): void {
        $dsn = 'sqlite:' . str_replace('\\', '/', $this->getDatabasePath());
        TestEnvironment::assertTestDatabase($dsn);

        file_put_contents("$this->root/e2e.env", implode("\n", [
            'APP_DEBUG=false',
            'LOG_LEVEL=6',
            "DSN=\"$dsn\"",
            'DB_USER=',
            'DB_PASSWORD=',
            'DB_PREFIX="' . self::TABLE_PREFIX . '"',
            "WEB_ROOT=\"{$this->getBaseUrl()}\"",
            'MODULES="Blog,Games,Books,Music,Backup"',
            'NODE_PATH=',
            'VIPSTHUMBNAIL_PATH=',
            'API_TOKEN_HEADER="X-BC-Token"',
            'API_AUTH_ENABLED=true',
            'API_ALLOW_ORIGIN=',
            'TRUSTED_PROXIES=',
        ]) . "\n");
    }

    private function getFreePort(): int {
        $socket = stream_socket_server('tcp://127.0.0.1:0');

        if ($socket === false) {
            throw new RuntimeException('Cannot find a free port.');
        }

        $name = (string)stream_socket_get_name($socket, false);
        fclose($socket);

        return (int)substr($name, (int)strrpos($name, ':') + 1);
    }

    private function waitUntilReady(): void {
        $deadline = microtime(true) + 15;

        while (microtime(true) < $deadline) {
            $connection = @fsockopen('127.0.0.1', $this->port, $errorCode, $errorMessage, 0.2);

            if ($connection !== false) {
                fclose($connection);

                return;
            }

            usleep(50_000);
        }

        throw new RuntimeException("The PHP built-in server has not started:\n" . $this->getLog());
    }
}
