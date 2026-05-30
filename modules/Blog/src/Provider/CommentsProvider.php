<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Provider;

use BC\Core\Auth\IAuth;
use BC\Modules\Blog\Model\Post;
use BC\Provider\ICommentsProvider;
use Runway\Exception\Exception;

readonly class CommentsProvider implements ICommentsProvider {
    public function __construct(
        private ICommentsProvider $inner,
        private IAuth $auth
    ) {
    }

    public function getSuccessMessages(): array {
        return $this->inner->getSuccessMessages();
    }

    public function isPageExist(string $pageType, int $pageId): bool {
        if ($pageType === 'post') {
            $conditions = ['id' => $pageId];

            if (!$this->auth->isAuthenticated()) {
                $conditions['published'] = true;
            }

            try {
                return (bool) Post::findOne($conditions);
            } catch (Exception) {
                return false;
            }
        }

        return $this->inner->isPageExist($pageType, $pageId);
    }
}
