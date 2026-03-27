<?php

namespace BC\Core\Command;

use BC\Core\Asset\IAssetBuilder;
use Runway\Console\Command\ACommand;
use Runway\Console\Input\IInput;
use Runway\Console\Output\IOutput;

class AssetsBuildCommand extends ACommand
{
    public function __construct(
        private readonly IAssetBuilder $assetBundler,
    ) {
        parent::__construct();
    }

    public function getName(): string
    {
        return 'assets:build';
    }

    public function getDescription(): string
    {
        return 'Build and minify assets';
    }

    protected function execute(IInput $input, IOutput $output): int
    {
        $output->info('Building assets...');

        $this->assetBundler->buildAssets();

        $output->success('Assets built successfully.');

        return 0;
    }
}
