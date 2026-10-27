<?php

declare(strict_types=1);

namespace BC\Tests\Fixture\Backup;

use BC\Provider\IPathsProvider;
use LogicException;

/**
 * The paths of a test: only the static folder is known.
 */
final readonly class PathsProviderStub implements IPathsProvider {
    public function __construct(
        private string $staticPath
    ) {}

    public function getTemplatePaths(): array {
        throw new LogicException('Not expected in the test.');
    }

    public function getWidgetPaths(): array {
        throw new LogicException('Not expected in the test.');
    }

    public function getAssetPaths(): array {
        throw new LogicException('Not expected in the test.');
    }

    public function getStaticPath(): string {
        return $this->staticPath;
    }

    public function getStaticWebPath(): string {
        throw new LogicException('Not expected in the test.');
    }

    public function getVersionedStaticWebPath(string $relativePath): string {
        throw new LogicException('Not expected in the test.');
    }

    public function getImagesPath(): string {
        throw new LogicException('Not expected in the test.');
    }

    public function getImagesWebPath(): string {
        throw new LogicException('Not expected in the test.');
    }
}
