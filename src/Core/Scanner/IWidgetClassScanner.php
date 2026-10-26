<?php

declare(strict_types=1);

namespace BC\Core\Scanner;

interface IWidgetClassScanner {
    /**
     * Scans widget directories and returns FQCNs of all found classes.
     *
     * @return list<class-string>
     */
    public function getWidgetClasses(): array;
}
