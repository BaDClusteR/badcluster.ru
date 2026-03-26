<?php

declare(strict_types=1);

namespace BC\Core\Trait;

use BC\Core\Config\IWebsiteSettings;
use Runway\Singleton\Container;

trait WebsiteSettingsTrait
{
    public function getWebsiteSettings(): IWebsiteSettings {
        return Container::getInstance()->getService(IWebsiteSettings::class);
    }
}