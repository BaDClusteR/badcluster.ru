<?php

declare(strict_types=1);

namespace BC\Core\Asset\Minifier;

use BC\Core\Helper\MixedValue;
use Runway\Env\Provider\IEnvVariablesProvider;
use Runway\Logger\ILogger;

class MinifierFactory implements IMinifier {
    private ?IMinifier $minifier = null;

    public function __construct(
        private readonly IEnvVariablesProvider $envVariablesProvider,
        private readonly ILogger $logger
    ) {
    }

    public function minify(string $content, string $type): string {
        return $this->getMinifier()->minify($content, $type);
    }

    private function getMinifier(): IMinifier {
        if ($this->minifier === null) {
            $nodePath = $this->envVariablesProvider->getEnvVariable('NODE_PATH');

            $this->minifier = $nodePath
                ? new NodeMinifier($this->logger, MixedValue::toString($nodePath))
                : new PhpMinifier();
        }

        return $this->minifier;
    }
}
