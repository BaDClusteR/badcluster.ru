<?php

declare(strict_types=1);

use BC\Tests\Support\TestEnvironment;

// PHPUnit has loaded it already, with bootstrap.php of the site: it has added .env.local to the env files.
require_once dirname(__DIR__) . '/vendor/autoload.php';

TestEnvironment::setUp();
