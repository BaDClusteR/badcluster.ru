<?php

namespace BC\Modules\Blog\Core\Action\Validator;

use BC\Exception\UnprocessableEntityException;
use BC\Modules\Blog\Core\Action\DTO\CreatePostRequest;
use BC\Modules\Blog\Core\Action\DTO\SavePostRequest;
use BC\Modules\Blog\Core\Action\DTO\ValidatorResponse;
use BC\Modules\Blog\Model\Post;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Model\Exception\ModelException;

class PostValidator implements IPostValidator
{
    /**
     * @throws ModelException
     * @throws DBException
     * @throws QueryBuilderException
     */
    public function validate(SavePostRequest|CreatePostRequest $request): ValidatorResponse
    {
        if ($postWithTheSameSlug = $this->getPostWithTheSameSlug($request)) {
            return new ValidatorResponse(
                successful: false,
                errors: [
                    'slug' => sprintf(
                        '"Такой слаг уже занят постом #%d "%s""',
                        $postWithTheSameSlug->getId(),
                        $postWithTheSameSlug->getTitle()
                    ),
                ]
            );
        }

        return new ValidatorResponse();
    }

    /**
     * @throws DBException
     * @throws ModelException
     * @throws QueryBuilderException
     */
    private function getPostWithTheSameSlug(SavePostRequest|CreatePostRequest $request): ?Post {
        $qb = Post::getQueryBuilder()
                  ->andWhere('LOWER(slug) = :slug')
                  ->setVariable('slug', strtolower($request->slug));

        if ($request instanceof SavePostRequest) {
            $qb = $qb->andWhere('id != :postId')
                     ->setVariable('postId', $request->id);
        }

        /** @var Post|null $result */
        $result = $qb->getFirstEntity();

        return $result;
    }
}
