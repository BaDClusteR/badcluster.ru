<?php

namespace BC\Provider\Admin;

use BC\DTO\AppSettings\AppSettingsDTO;
use BC\DTO\AppSettings\ModuleDTO;
use BC\DTO\AppSettings\NavigationDTO;

class AppSettingsProvider implements IAppSettingsProvider
{
    public function getAppSettings(): AppSettingsDTO
    {
        return new AppSettingsDTO(
            nav: $this->getNav(),
            modules: $this->getModules(),
        );
    }

    /**
     * @return NavigationDTO[]
     */
    private function getNav(): array {
        return [
            new NavigationDTO(
                label: "Дашборд",
                path: "/admin",
                icon: "dashboard",
            )
        ];
    }

    /**
     * @return ModuleDTO[]
     */
    private function getModules(): array {
        return [];
    }
}
