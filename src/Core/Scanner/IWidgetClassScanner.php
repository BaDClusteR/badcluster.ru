<?php

declare(strict_types=1);

namespace BC\Core\Scanner;

use BC\Widget\IAssetProvider;

interface IWidgetClassScanner {
    /**
     * Scans widget directories and returns FQCNs of all found classes.
     *
     * @return class-string<IAssetProvider>[]
     */
    public function getWidgetClasses(): array;
}
