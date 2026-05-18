<?php

namespace BC\Modules\Blog\Provider;

use BC\Core\Auth\IAuth;
use BC\Modules\Blog\Model\Post;
use Runway\Exception\Exception;

class CommentsProvider extends \BC\Provider\CommentsProvider {
    public function __construct(
        private readonly IAuth $auth
    ) {
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

        return parent::isPageExist($pageType, $pageId);
    }
}
