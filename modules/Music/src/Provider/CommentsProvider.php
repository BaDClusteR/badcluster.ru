<?php

declare(strict_types=1);

namespace BC\Modules\Music\Provider;

use BC\Modules\Music\Model\Album;
use BC\Provider\ICommentsProvider;
use Runway\Exception\Exception;

readonly class CommentsProvider implements ICommentsProvider {
    public function __construct(
        private ICommentsProvider $inner
    ) {
    }

    public function getSuccessMessages(): array {
        return $this->inner->getSuccessMessages();
    }

    public function isPageExist(string $pageType, int $pageId): bool {
        if ($pageType === 'album') {
            try {
                return (bool) Album::findByUniqueIdentifier($pageId);
            } catch (Exception) {
                return false;
            }
        }

        return $this->inner->isPageExist($pageType, $pageId);
    }
}
