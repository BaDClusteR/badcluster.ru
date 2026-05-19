<?php

namespace BC\Modules\Blog\Core\Action\Tag;

use BC\Modules\Blog\Core\Action\DTO\CreateTagRequest;
use BC\Modules\Blog\Core\Action\DTO\CreateTagResponse;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;
use BC\Modules\Blog\Model\Tag;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Model\Exception\ModelException;

class CreateTagAction implements ICreateTagAction {
    /**
     * @throws ModelException
     * @throws DBException
     * @throws ActionValidationException
     * @throws QueryBuilderException
     */
    public function run(CreateTagRequest $request): CreateTagResponse {
        $this->validate($request);

        $tag = new Tag();
        $tag->setTitle($request->name)
            ->setSlug($request->slug);

        $tag->persist();

        return new CreateTagResponse($tag);
    }

    /**
     * @throws ModelException
     * @throws DBException
     * @throws QueryBuilderException
     * @throws ActionValidationException
     */
    private function validate(CreateTagRequest $request): void {
        /** @var Tag|null $tagBySlug */
        $tagBySlug = Tag::findOne(['slug' => $request->slug]);
        if ($tagBySlug) {
            throw new ActionValidationException(['slug' => "Этот слаг уже занят тэгом '{$tagBySlug->getTitle()}'"]);
        }

        $tagByTitle = Tag::findOne(['title' => $request->name]);
        if ($tagByTitle) {
            throw new ActionValidationException(['title' => 'Уже есть тэг с таким названием']);
        }
    }
}
