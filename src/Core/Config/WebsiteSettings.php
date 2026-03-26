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
}
