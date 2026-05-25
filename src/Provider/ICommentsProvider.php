<?php

declare(strict_types=1);

namespace BC\Provider;

interface ICommentsProvider {
    /**
     * @return string[]
     */
    public function getSuccessMessages(): array;

    public function isPageExist(string $pageType, int $pageId): bool;
}
