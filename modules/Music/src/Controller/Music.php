<?php

declare(strict_types=1);

namespace BC\Modules\Music\Controller;

use BC\Core\Response\SuccessfulHtmlResponse;
use BC\Core\Trait\Controller404Trait;
use BC\Modules\Music\Model\Album;
use BC\Modules\Music\Widget\Page\AlbumsListPage;
use BC\Modules\Music\Widget\Page\ReleasePage;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Logger\ILogger;
use Runway\Model\Exception\ModelException;
use Runway\Request\Response;

readonly class Music {
    use Controller404Trait;

    public function __construct(
        private ILogger $logger
    ) {
    }

    /**
     * @throws DBException
     * @throws ModelException
     * @throws QueryBuilderException
     */
    public function renderAlbumsList(): Response {
        return new SuccessfulHtmlResponse(
            new AlbumsListPage()->render([
                'albums' => $this->getAlbums(),
            ])
        );
    }

    /**
     * @throws DBException
     * @throws ModelException
     * @throws QueryBuilderException
     */
    protected function getAlbums(): array {
        return Album::find([], ['position', 'ASC']);
    }

    /**
     * @throws ModelException
     * @throws DBException
     * @throws QueryBuilderException
     */
    public function renderReleasePage(string $releaseSlug): Response {
        $release = Album::findOne(['slug' => $releaseSlug]);

        if (!$release) {
            return $this->get404Controller()->run();
        }

        return new SuccessfulHtmlResponse(
            new ReleasePage(['album' => $release])->render()
        );
    }
}
