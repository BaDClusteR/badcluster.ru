<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Core\Command;

use BC\Core\Asset\IAssetBuilder;
use BC\Core\Command\AssetsBuildCommand;
use BC\Tests\Support\BufferedOutput;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Runway\Console\Input\Input;

#[CoversClass(AssetsBuildCommand::class)]
final class AssetsBuildCommandTest extends TestCase {
    public function testAssetsAreBuilt(): void {
        $builder = new class implements IAssetBuilder {
            /** @var list<string> */
            public array $calls = [];

            public function addFile(string $bundleName, string $relativePath, int $priority = 100): void {
                $this->calls[] = 'addFile';
            }

            public function buildAssets(): void {
                $this->calls[] = 'buildAssets';
            }

            public function buildBundles(): void {
                $this->calls[] = 'buildBundles';
            }

            public function getBundleFileName(string $bundleName, string $type): ?string {
                return null;
            }

            public function getBundleWebPath(string $bundleName, string $type): ?string {
                return null;
            }
        };
        $command = new AssetsBuildCommand($builder);
        $output = new BufferedOutput();

        self::assertSame('assets:build', $command->getName());
        self::assertSame('Build and minify assets', $command->getDescription());
        self::assertSame(0, $command->run(new Input(), $output));
        self::assertSame(['buildAssets'], $builder->calls);
        self::assertSame("Building assets...\nAssets built successfully.\n", $output->stdout);
    }
}
