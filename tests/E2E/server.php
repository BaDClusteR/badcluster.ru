<?php

/**
 * The router script of the E2E tests ("php -S 127.0.0.1:<port> -t <project> tests/E2E/server.php", see
 * BC\Tests\Support\HttpServer): the files are served as is, the rest goes to the kernel like in index.php, with the
 * environment of the test (BC_E2E_ENV_FILE) instead of .env.local.
 */

declare(strict_types=1);

use BC\Tests\Support\TestEnvironment;
use Runway\Singleton\Kernel;

$projectRoot = dirname(__DIR__, 2);
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = (string)parse_url(is_string($uri) ? $uri : '/', PHP_URL_PATH);

if ($path !== '/' && !str_ends_with(strtolower($path), '.php') && is_file($projectRoot . $path)) {
    return false;
}

require_once $projectRoot . '/vendor/autoload.php';

$envFile = (string)getenv('BC_E2E_ENV_FILE');

if ($envFile === '' || !is_file($envFile)) {
    http_response_code(500);
    echo 'No environment of the E2E tests.';

    return true;
}

TestEnvironment::replaceEnvFiles($envFile);
TestEnvironment::assertTestDatabase(TestEnvironment::getDsn());

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header_remove('X-Powered-By');

Kernel::getInstance()->processRequest();

return true;
