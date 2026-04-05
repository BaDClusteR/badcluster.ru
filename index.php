<?php

/**
 * TODO: Двинуть в Runway file system рекурсивное копирование папок
 * TODO: пересмотреть все ворнинги в проекте
 * TODO: тесты основного функционала
 * TODO: добавить terser и lightningcss в node зависимости
 */

declare(strict_types=1);

use Runway\Singleton\Kernel;

require_once __DIR__ . "/vendor/autoload.php";

$kernel = Kernel::getInstance();
$kernel->processRequest();
