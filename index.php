<?php

/**
 * TODO: Двинуть в Runway file system рекурсивное копирование папок
 * TODO: пересмотреть все ворнинги в проекте
 * TODO: тесты основного функционала
 * TODO: добавить terser и lightningcss в node зависимости
 * TODO: Доработать Auth
 * TODO: Брать таймзону из конфига?
 *
 * TODO: [Runway] nullable в DataStoragePropertiesConverter (на примере дата - инт и наоборот - нет знаний,
 *       nullable ли то, куда и откуда конвертим)
 *
 * TODO: [API platform] переписать Auth - он вообще ничего не должен знать ни о каких токенах, должен только проверять:
 *       аутентифицирован? Да / Нет
 * TODO: [API platform] Убрать захардкоженные рауты и эндпоинты (и может как-то переписать так, чтобы структуру урлов
 *       сделать менее строгой)
 * TODO: [API platform] При превращении DTO в JSON не конвертить UTF8
 */

declare(strict_types=1);

use Runway\Singleton\Kernel;

require_once __DIR__ . "/vendor/autoload.php";

$kernel = Kernel::getInstance();
$kernel->processRequest();
