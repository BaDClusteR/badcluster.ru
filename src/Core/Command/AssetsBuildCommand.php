<?php

namespace BC\Core\Command;

use BC\Core\Asset\IAssetBundler;
use Runway\Console\Command\ACommand;
use Runway\Console\Input\IInput;
use Runway\Console\Output\IOutput;

class AssetsBuildCommand extends ACommand
{
    public function __construct(
        private readonly IAssetBundler $assetBundler,
    ) {
        parent::__construct();
    }

    public function getName(): string
    {
        return 'assets:build';
    }

    public function getDescription(): string
    {
        return 'Build and minify asset bundles';
    }

    protected function execute(IInput $input, IOutput $output): int
    {
        $output->info('Building asset bundles...');

        $this->assetBundler->buildBundles();

        $output->success('Asset bundles built successfully.');

        return 0;
    }
}
