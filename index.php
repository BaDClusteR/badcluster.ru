<?php

use Runway\Singleton\Kernel;

require_once __DIR__ . "/vendor/autoload.php";

$kernel = Kernel::getInstance();
$kernel->processRequest();