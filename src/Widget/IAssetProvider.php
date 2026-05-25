<?php

declare(strict_types=1);

namespace BC\Widget;

use BC\Core\Asset\DTO\AssetDTO;

interface IAssetProvider {
    /**
     * Returns the list of assets this widget contributes to bundles.
     *
     * @return AssetDTO[]
     */
    public static function getAssets(): array;
}
