<?php

namespace BC\Modules\Blog\Core\Action\Post;

use BC\Core\Trait\BlockHelperTrait;
use BC\Modules\Blog\Core\Action\DTO\CreatePostRequest;
use BC\Modules\Blog\Core\Action\DTO\SavePostRequest;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;
use BC\Modules\Blog\Core\Action\Validator\IPostValidator;
use BC\Modules\Blog\Model\Post;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Model\Exception\ModelException;
use Runway\Singleton\Container;

abstract class APostAction
{
    use BlockHelperTrait;

    /**
     * @throws DBException
     * @throws QueryBuilderException
     * @throws ModelException
     */
    protected function syncModel(Post $post, CreatePostRequest|SavePostRequest $request): void {
        $post->setTitle($request->title)
             ->setShortTitle($request->shortTitle)
             ->setAnnotation($request->annotation)
             ->setContent(
                 $this->getBlockHelper()->cleanBlocks(
                     $request->content
                 )
             )
             ->setSlug($request->slug)
             ->setPublished($request->published)
             ->setPublishDate($request->publishDate)
             ->setUpdateDate($request->updateDate)
             ->setCover($request->coverImage)
             ->setMetaDescription($request->metaDescription);

        if ($request->coverImage) {
            $request->coverImage->setAlt($request->coverImageAltText);
            $request->coverImage->persist();
        }

        $post->persist();

        $post->syncTags($request->tags);
    }

    protected function getValidator(): IPostValidator {
        return Container::getInstance()->getService(IPostValidator::class);
    }

    /**
     * @throws ActionValidationException
     */
    protected function validate(CreatePostRequest|SavePostRequest $request): void {
        $validationResponse = $this->getValidator()->validate($request);

        if (!$validationResponse->successful) {
            throw new ActionValidationException($validationResponse->errors);
        }
    }
}
