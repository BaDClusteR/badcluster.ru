<?php

namespace BC\Core\Config;

use Runway\Env\Provider\IEnvVariablesProvider;

class WebsiteSettings implements IWebsiteSettings
{
    public function __construct(
        protected IEnvVariablesProvider $envVars
    ) {
    }

    public function getWebRoot(): string
    {
        return (string)$this->envVars->getEnvVariable('WEB_ROOT');
    }

    public function getImageBreakpoints(): array {
        return [
            450 => 500,
            -1  => 1000
        ];
    }
}
