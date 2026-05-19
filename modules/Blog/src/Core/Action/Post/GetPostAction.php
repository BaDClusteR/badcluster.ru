<?php

namespace BC\Modules\Blog\Core\Action\Post;

use BC\Modules\Blog\Core\Action\DTO\GetPostRequest;
use BC\Modules\Blog\Core\Action\DTO\GetPostResponse;
use BC\Modules\Blog\Model\Post;
use Runway\Exception\Exception;

readonly class GetPostAction implements IGetPostAction {
    /**
     * @throws Exception
     */
    public function run(GetPostRequest $request): GetPostResponse {
        $post = Post::findByUniqueIdentifier($request->id);

        if (!$post) {
            throw new Exception("Post #$request->id not found");
        }

        return new GetPostResponse($post);
    }
}
