<?php

namespace BC\Modules\Blog\Widget;

use BC\Core\Trait\AuthTrait;
use BC\Core\Trait\ConverterTrait;
use BC\Modules\Blog\Model\Post;
use BC\Widget\AWidget;
use BC\Widget\Common\Picture;
use DateTime;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Model\Exception\ModelException;

class Posts extends AWidget
{
    use AuthTrait;
    use ConverterTrait;

    /**
     * @throws ModelException
     * @throws DBException
     * @throws QueryBuilderException
     *
     * @return iterable<Post>
     */
    protected function getPosts(): iterable {
        $conditions = [];
        if (!$this->isAuthorised()) {
            $conditions = ['published' => true];
        }

        return Post::iterate($conditions, ["publish_date", "DESC"]);
    }

    protected function getTemplatePath(): string
    {
        return 'modules/Blog/posts.phtml';
    }

    private function isAuthorised(): bool {
        return $this->getAuth()->isAuthenticated();
    }

    protected function getDateValue(?DateTime $date): string {
        return $date?->format("Y-m-d") ?? "";
    }

    protected function getHumanReadableDate(?DateTime $date): string {
        return $date
            ? $this->getConverter()->convertTimestampToHumanReadableDate($date->getTimestamp())
            : "";
    }

    protected function renderMiniature(Post $post): string {
        if ($miniature = $post->getCover()) {
            return new Picture([
                'image'        => $miniature,
                'pictureClass' => 'post__miniature',
                'lazyLoad'     => true,
                'breakpoints'  => [
                    -1  => 200
                ]
            ])->render();
        }

        return '';
    }
}
