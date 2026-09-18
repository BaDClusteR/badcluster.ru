<?php

declare(strict_types=1);

namespace BC\Provider;

use BC\Model\StaticPage;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Model\Exception\ModelException;

readonly class StaticPagesProvider implements IStaticPagesProvider {
    /**
     * @throws DBException
     * @throws ModelException
     * @throws QueryBuilderException
     */
    public function getBySlug(string $slug, bool $onlyPublished): ?StaticPage {
        if (trim($slug) === '') {
            return null;
        }

        $qb = StaticPage::getQueryBuilder()
                        ->andWhere('LOWER(slug) = :slug')
                        ->setVariable('slug', strtolower($slug));

        if ($onlyPublished) {
            $qb = $qb->andWhere('published = 1');
        }

        /** @var StaticPage|null $page */
        $page = $qb->getFirstEntity();

        return $page;
    }

    /**
     * @return StaticPage[]
     *
     * @throws DBException
     * @throws ModelException
     * @throws QueryBuilderException
     */
    public function getPublishedPages(): array {
        return StaticPage::getQueryBuilder()
                         ->andWhere('published = 1')
                         ->orderBy('id', 'ASC')
                         ->getEntities();
    }
}
