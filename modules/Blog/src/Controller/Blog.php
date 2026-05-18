<?php

namespace BC\Modules\Blog\Controller;

use BC\Core\Auth\IAuth;
use BC\Core\Response\SuccessfulHtmlResponse;
use BC\Core\Trait\Controller404Trait;
use BC\Modules\Blog\Model\Post;
use BC\Modules\Blog\Widget\Page\BlogPage;
use BC\Modules\Blog\Widget\Page\PostPage;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Model\Exception\ModelException;
use Runway\Request\Response;

readonly class Blog {
    use Controller404Trait;

    public function __construct(
        private IAuth $auth
    ) {
    }

    public function renderPostList(): Response {
        return new SuccessfulHtmlResponse(
            new BlogPage()->render()
        );
    }

    /**
     * @throws ModelException
     * @throws DBException
     * @throws QueryBuilderException
     */
    public function renderPost(string $slug): Response {
        $post = $this->getPost($slug);

        if (!$post) {
            return $this->get404Controller()->run();
        }

        return new SuccessfulHtmlResponse(
            new PostPage(['post' => $post])->render()
        );
    }

    /**
     * @throws ModelException
     * @throws DBException
     * @throws QueryBuilderException
     */
    private function getPost(string $slug): ?Post {
        $conditions = ['slug' => $slug];

        if (!$this->auth->isAuthenticated()) {
            $conditions['published'] = true;
        }

        return Post::findOne($conditions);
    }
}
