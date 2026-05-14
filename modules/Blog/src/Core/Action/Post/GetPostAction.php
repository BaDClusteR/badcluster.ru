<?php

namespace BC\Modules\Blog\Core\Action\Post;

use BC\Core\Converter\Media\IMediaConverter;
use BC\Model\Media;
use BC\Modules\Blog\Core\Action\DTO\GetPostRequest;
use BC\Modules\Blog\Core\Action\DTO\GetPostResponse;
use BC\Modules\Blog\Model\Post;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Exception\Exception;
use Runway\Model\Exception\ModelException;

readonly class GetPostAction implements IGetPostAction
{
    public function __construct(
        private IMediaConverter $mediaConverter
    ) {
    }

    /**
     * @throws Exception
     */
    public function run(GetPostRequest $request): GetPostResponse
    {
        $post = Post::findByUniqueIdentifier($request->id);

        if (!$post) {
            throw new Exception("Post #$request->id not found");
        }

        return new GetPostResponse($post);
    }
}
