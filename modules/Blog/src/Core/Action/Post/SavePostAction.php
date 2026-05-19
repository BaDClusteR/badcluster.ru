<?php

namespace BC\Modules\Blog\Core\Action\Post;

use BC\Modules\Blog\Core\Action\DTO\Event\SavedPostEnvelope;
use BC\Modules\Blog\Core\Action\DTO\Event\SavePostEnvelope;
use BC\Modules\Blog\Core\Action\DTO\SavePostRequest;
use BC\Modules\Blog\Model\Post;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Event\IEventDispatcher;
use Runway\Exception\Exception;
use Runway\Model\Exception\ModelException;

class SavePostAction extends APostAction implements ISavePostAction {
    public function __construct(
        private readonly IEventDispatcher $eventDispatcher
    ) {
    }

    /**
     * @throws Exception
     */
    public function run(SavePostRequest $request): void {
        $this->eventDispatcher->dispatch(
            'post.save.before',
            new SavePostEnvelope($request)
        );

        $this->validate($request);

        $post = Post::findByUniqueIdentifier($request->id);

        if (!$post) {
            throw new Exception("Post #$request->id not found");
        }

        $this->doRun($post, $request);

        $this->eventDispatcher->dispatch(
            'post.save.after',
            new SavedPostEnvelope($request, $post)
        );
    }

    /**
     * @throws DBException
     * @throws ModelException
     * @throws QueryBuilderException
     */
    protected function doRun(Post $post, SavePostRequest $request): void {
        if (
            ($cover = $post->getCover())
            && $cover->getId() !== $request->coverImage?->getId()
        ) {
            $cover->remove();
            $post->setCover(null);
        }

        $this->syncModel($post, $request);
    }
}
