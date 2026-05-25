<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Provider;

use BC\Modules\Blog\Model\Post;
use BC\Modules\Blog\Model\Tag;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Enum\ExpressionJoinConditionTypeEnum;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\DataStorage\QueryBuilder\IQueryBuilder;
use Runway\Exception\Exception;
use Runway\Logger\ILogger;
use Runway\Model\Exception\ModelException;

readonly class PostsProvider implements IPostsProvider {
    public function __construct(
        private ILogger $logger
    ) {
    }

    public function getPosts(string $tag, int $page, bool $onlyPublished): ?iterable {
        try {
            $qb = $this->prepareQueryBuilder($tag, $onlyPublished);
        } catch (Exception $e) {
            $this->logger->error(
                sprintf('[%s] Cannot get posts: %s', __METHOD__, $e->getMessage()),
                $this->getLogContext($e, $tag, $page, $onlyPublished)
            );

            return null;
        }

        try {
            $count = $qb->count();
        } catch (Exception $e) {
            $this->logger->error(
                sprintf('[%s] Cannot count posts: %s', __METHOD__, $e->getMessage()),
                $this->getLogContext($e, $tag, $page, $onlyPublished)
            );

            return null;
        }

        $showBy = $this->getShowBy();
        $offset = ($page - 1) * $showBy;

        if ($count <= $offset) {
            return null;
        }

        $qb->orderBy('publish_date', 'DESC')
           ->setLimit($offset, $showBy);

        try {
            return $qb->iterateEntities();
        } catch (Exception $e) {
            $this->logger->error(
                sprintf('[%s] Cannot iterate posts: %s', __METHOD__, $e->getMessage()),
                $this->getLogContext($e, $tag, $page, $onlyPublished)
            );

            return null;
        }
    }

    public function getShowBy(): int {
        return 20;
    }

    /**
     * @throws DBException
     * @throws ModelException
     * @throws QueryBuilderException
     */
    private function prepareQueryBuilder(string $tag, bool $onlyPublished): IQueryBuilder {
        $qb = Post::getQueryBuilder('p');

        if ($onlyPublished) {
            $qb->andWhere('published = :published')
               ->setVariable('published', true);
        }

        if ($tag) {
            $tagId = Tag::findOne(['slug' => $tag])?->getId() ?? 0;

            if ($tagId) {
                $qb->select('p.*')
                   ->leftJoin('post_tags', 'pt', ExpressionJoinConditionTypeEnum::ON, 'pt.post_id = p.id')
                   ->andWhere('pt.tag_id = :tag')
                   ->setVariable('tag', $tagId);
            }
        }

        return $qb;
    }

    private function getLogContext(Exception $e, string $tag, int $page, bool $onlyPublished): array {
        return [
            'tag'           => $tag,
            'page'          => $page,
            'onlyPublished' => $onlyPublished,
            'errCode'       => $e->getCode(),
            'errMessage'    => $e->getMessage(),
        ];
    }

    public function getTotalPostsCount(string $tag, bool $onlyPublished): int {
        try {
            return $this->prepareQueryBuilder($tag, $onlyPublished)->count();
        } catch (Exception $e) {
            $this->logger->error(
                sprintf('[%s] Cannot get posts count: %s', __METHOD__, $e->getMessage()),
                [
                    'tag'           => $tag,
                    'onlyPublished' => $onlyPublished,
                    'errCode'       => $e->getCode(),
                    'errMessage'    => $e->getMessage(),
                ]
            );

            return 0;
        }
    }
}
