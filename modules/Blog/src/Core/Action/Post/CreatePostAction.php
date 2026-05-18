<?php

namespace BC\Modules\Blog\Core\Action\Post;

use BC\Modules\Blog\Core\Action\DTO\CreatePostRequest;
use BC\Modules\Blog\Core\Action\DTO\CreatePostResponse;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;
use BC\Modules\Blog\Model\Post;
use DateTime;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Model\Exception\ModelException;

class CreatePostAction extends APostAction implements ICreatePostAction {
    /**
     * @throws ActionValidationException
     * @throws DBException
     * @throws ModelException
     * @throws QueryBuilderException
     */
    public function run(CreatePostRequest $request): CreatePostResponse {
        $this->validate($request);

        $post = new Post();
        $this->doRun($post, $request);

        return new CreatePostResponse(
            post: $post
        );
    }

    /**
     * @throws DBException
     * @throws ModelException
     * @throws QueryBuilderException
     */
    protected function doRun(Post $post, CreatePostRequest $request): void {
        $post->setCreatedDate(
            new DateTime('now')
        );

        $this->syncModel($post, $request);
    }
}
