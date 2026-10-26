<?php

declare(strict_types=1);

namespace BC\Core\Media\Processor\Command;

use BC\Core\Helper\MixedValue;
use Runway\Env\Provider\IEnvVariablesProvider;
use Runway\Singleton\Container;

abstract class ACommand implements ICommand {
    protected function getVipsThumbnailPath(): string {
        return MixedValue::toString($this->getEnvVariablesProvider()->getEnvVariable('VIPSTHUMBNAIL_PATH'));
    }

    private function getEnvVariablesProvider(): IEnvVariablesProvider {
        return Container::getInstance()->getService(IEnvVariablesProvider::class);
    }
}
