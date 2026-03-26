<?php

use Runway\Service\Provider\PathsProvider;

const PROJECT_ROOT = __DIR__;
const PROJECT_SRC = __DIR__ . '/src';
const BC_CONFIG_ROOT = PROJECT_ROOT . "/config";
const MODULE_ROOT = PROJECT_ROOT . "/modules";

$pathsProvider = PathsProvider::getInstance();
$pathsProvider->addConfigDirectory(BC_CONFIG_ROOT);
$pathsProvider->addEnvFilePath(PROJECT_ROOT . "/.env");
$pathsProvider->addEnvFilePath(PROJECT_ROOT . "/.env.local");