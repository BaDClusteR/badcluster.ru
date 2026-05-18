<?php

namespace BC\Provider;

interface ICommentsProvider {
    /**
     * @return string[]
     */
    public function getSuccessMessages(): array;

    public function isPageExist(string $pageType, int $pageId): bool;
}
