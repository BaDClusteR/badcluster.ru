<?php

declare(strict_types=1);

namespace BC\Core\Event;

use BC\Model\Config;
use Exception;

class Kernel {
    public function onInit(): void {
        try {
            if ($timezone = Config::findOne(['name' => 'timezone'])?->getValue()) {
                date_default_timezone_set($timezone);
            }
        } catch (Exception) {
        }
    }
}
