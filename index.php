<?php

/**
 * TODO: Двинуть в Runway file system рекурсивное копирование папок
 * TODO: пересмотреть все ворнинги в проекте
 * TODO: тесты основного функционала
 * TODO: добавить terser и lightningcss в node зависимости
 *
 * TODO: !!!Если при генерации видим, что уже есть тамбнейл такой ширины и майм тайпа - либо пропускаем, либо удаляем
 *       его, в зависимости от какой-нибудь настройки.
 */

declare(strict_types=1);

use Runway\Singleton\Kernel;

require_once __DIR__ . "/vendor/autoload.php";

$kernel = Kernel::getInstance();
$kernel->processRequest();
