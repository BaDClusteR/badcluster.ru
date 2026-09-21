<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Controller;

use BC\Core\Auth\IAuth;
use BC\Core\Exception\ImageException;
use BC\Core\Response\RedirectResponse;
use BC\Core\Response\SuccessfulHtmlResponse;
use BC\Core\Trait\Controller404Trait;
use BC\Modules\Blog\Core\Media\IPostPreviewGenerator;
use BC\Modules\Blog\Model\Post;
use BC\Modules\Blog\Provider\IPostsProvider;
use BC\Modules\Blog\Widget\Page\BlogPage;
use BC\Modules\Blog\Widget\Page\PostPage;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Model\Exception\ModelException;
use Runway\Request\Response;
use Throwable;

readonly class Blog {
    use Controller404Trait;

    public function __construct(
        private IAuth $auth,
        private IPostsProvider $postsProvider
    ) {
    }

    /**
     * @throws Throwable
     */
    public function renderPostList(string $tag = '', string $page = ''): Response {
        if ($page) {
            if (!is_numeric($page)) {
                return $this->get404Controller()->run();
            }

            $pageNum = (int) $page;
            if ($pageNum < 1) {
                return $this->get404Controller()->run();
            }

            if ($pageNum === 1) {
                return new RedirectResponse('/blog');
            }
        } else {
            $pageNum = 1;
        }

        $onlyPublished = !$this->auth->isAuthenticated();
        $posts = $this->postsProvider->getPosts($tag, $pageNum, $onlyPublished);
        if (!$posts) {
            return $this->get404Controller()->run();
        }

        return new SuccessfulHtmlResponse(
            new BlogPage()->render([
                'page'  => $pageNum,
                'total' => $this->postsProvider->getTotalPostsCount($tag, $onlyPublished),
                'tag'   => $tag,
            ])
        );
    }

    /**
     * @throws Throwable
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
    private function getPost(string $slug = '', int $id = 0): ?Post {
        $conditions = $id
            ? ['id' => $id]
            : ['slug' => $slug];

        if (!$this->auth->isAuthenticated()) {
            $conditions['published'] = true;
        }

        return Post::findOne($conditions);
    }
}
