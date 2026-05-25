<?php

declare(strict_types=1);

namespace BC\Provider\Admin;

use BC\DTO\AppSettings\AppSettingsDTO;

interface IAppSettingsProvider {
    public function getAppSettings(): AppSettingsDTO;
}
