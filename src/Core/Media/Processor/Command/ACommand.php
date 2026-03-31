<?php

namespace BC\Core\Media\Processor\Command;

use Runway\Env\Provider\IEnvVariablesProvider;
use Runway\Singleton\Container;

abstract class ACommand implements ICommand
{
    protected function getVipsThumbnailPath(): string {
        return (string)$this->getEnvVariablesProvider()->getEnvVariable('VIPSTHUMBNAIL_PATH');
    }

    private function getEnvVariablesProvider(): IEnvVariablesProvider {
        return Container::getInstance()->getService(IEnvVariablesProvider::class);
    }
}
