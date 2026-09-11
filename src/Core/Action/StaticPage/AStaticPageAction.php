<?php

declare(strict_types=1);

namespace BC\Core\Action\StaticPage;

use BC\Core\Action\DTO\CreateStaticPageRequest;
use BC\Core\Action\DTO\SaveStaticPageRequest;
use BC\Core\Trait\BlockHelperTrait;
use BC\Model\StaticPage;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Model\Exception\ModelException;

abstract class AStaticPageAction {
    use BlockHelperTrait;

    /** Matches the single-segment route wildcard these pages are served from. */
    private const string SLUG_PATTERN = '/^[A-Za-z0-9_-]+$/';

    /**
     * @throws ActionValidationException
     * @throws DBException
     * @throws ModelException
     * @throws QueryBuilderException
     */
    protected function validate(CreateStaticPageRequest|SaveStaticPageRequest $request): void {
        $errors = [];

        if (trim($request->title) === '') {
            $errors['title'] = 'Укажите заголовок страницы';
        }

        $slug = trim($request->slug);

        if ($slug === '') {
            $errors['slug'] = 'Укажите слаг страницы';
        } elseif (!preg_match(self::SLUG_PATTERN, $slug)) {
            $errors['slug'] = 'Слаг может содержать только латиницу, цифры, дефис и подчёркивание';
        } elseif ($taken = $this->findPageWithTheSameSlug($request)) {
            $errors['slug'] = sprintf(
                'Такой слаг уже занят страницей #%d "%s"',
                $taken->getId(),
                $taken->getTitle()
            );
        }

        if ($errors) {
            throw new ActionValidationException($errors);
        }
    }

    protected function syncModel(StaticPage $page, CreateStaticPageRequest|SaveStaticPageRequest $request): void {
        $page->setTitle(trim($request->title))
             ->setShortTitle(trim($request->shortTitle))
             // The other half of enrichBlocks(): strips media back down to an
             // id before storing, and writes edited alt/size back onto Media.
             ->setContent(
                 $this->getBlockHelper()->cleanBlocks(
                     $request->content
                 )
             )
             ->setSlug(trim($request->slug))
             ->setPublished($request->published)
             ->setIndexable($request->indexable)
             ->setTextBlock($request->textBlock)
             ->setPublishDate($request->publishDate)
             ->setMetaDescription(trim($request->metaDescription))
             ->setBackLinkText(trim($request->backLinkText))
             ->setBackLinkUrl(trim($request->backLinkUrl))
             ->persist();
    }

    /**
     * Static pages have no path prefix of their own, so a slug could still
     * collide with a hardcoded route. There is no cheap way to enumerate those,
     * so uniqueness is only enforced among static pages.
     *
     * @throws DBException
     * @throws ModelException
     * @throws QueryBuilderException
     */
    private function findPageWithTheSameSlug(
        CreateStaticPageRequest|SaveStaticPageRequest $request
    ): ?StaticPage {
        $qb = StaticPage::getQueryBuilder()
                        ->andWhere('LOWER(slug) = :slug')
                        ->setVariable('slug', strtolower(trim($request->slug)));

        if ($request instanceof SaveStaticPageRequest) {
            $qb = $qb->andWhere('id != :pageId')
                     ->setVariable('pageId', $request->id);
        }

        /** @var StaticPage|null $result */
        $result = $qb->getFirstEntity();

        return $result;
    }
}
