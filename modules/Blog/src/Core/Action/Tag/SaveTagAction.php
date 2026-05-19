<?php

namespace BC\Modules\Blog\Core\Action\Tag;

use BC\Modules\Blog\Core\Action\DTO\SaveTagRequest;
use BC\Modules\Blog\Core\Action\DTO\SaveTagResponse;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;
use BC\Modules\Blog\Model\Tag;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Exception\Exception;
use Runway\Model\Exception\ModelException;

class SaveTagAction implements ISaveTagAction {
    /**
     * @throws ModelException
     * @throws DBException
     * @throws ActionValidationException
     * @throws QueryBuilderException
     * @throws Exception
     */
    public function run(SaveTagRequest $request): SaveTagResponse {
        $this->validate($request);

        $tag = Tag::findByUniqueIdentifier($request->id);

        if (!$tag) {
            throw new Exception("Tag #$request->id not found");
        }

        $tag->setTitle($request->name)
            ->setSlug($request->slug);

        $tag->persist();

        return new SaveTagResponse($tag);
    }

    /**
     * @throws ModelException
     * @throws DBException
     * @throws QueryBuilderException
     * @throws ActionValidationException
     */
    private function validate(SaveTagRequest $request): void {
        /** @var Tag|null $tagBySlug */
        $tagBySlug = Tag::getQueryBuilder()->where('slug = :slug')
            ->andWhere('id != :id')
            ->setVariable('slug', $request->slug)
            ->setVariable('id', $request->id)
            ->getFirstEntity();

        if ($tagBySlug) {
            throw new ActionValidationException(['slug' => "Этот слаг уже занят тэгом '{$tagBySlug->getTitle()}'"]);
        }

        $tagByTitle = Tag::getQueryBuilder()
            ->where('title = :title')
            ->andWhere('id != :id')
            ->setVariable('title', $request->name)
            ->setVariable('id', $request->id)
            ->getFirstEntity();

        if ($tagByTitle) {
            throw new ActionValidationException(['title' => 'Уже есть тэг с таким названием']);
        }
    }
}
