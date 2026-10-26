<?php

declare(strict_types=1);

namespace BC\Tests\Support;

use Runway\Env\Provider\IEnvVariablesProvider;

/**
 * The env variables of a test.
 */
final readonly class EnvStub implements IEnvVariablesProvider {
    /**
     * @param array<string, mixed> $variables
     */
    public function __construct(
        private array $variables = []
    ) {}

    public function getEnvVariable(string $variableName): mixed {
        return $this->variables[$variableName] ?? null;
    }
}
