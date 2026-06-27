<?php

declare(strict_types=1);

namespace BC\Modules\Music\Provider\Admin;

use BC\DTO\PageDTO;
use BC\Modules\Music\Model\Album;
use BC\Provider\Admin\IPageProvider;
use Runway\Exception\Exception;

readonly class PageProvider implements IPageProvider {
    public function __construct(
        private IPageProvider $inner
    ) {
    }

    public function getPage(string $pageType, int $pageId): PageDTO {
        if ($pageType === 'album') {
            try {
                $album = Album::findByUniqueIdentifier($pageId);

                return $album
                    ? new PageDTO(
                        sprintf('Музыкальный сборник "%s"', $album->getTitle()),
                        "/admin/music/{$album->getId()}"
                    )
                    : new PageDTO('Неизвестный музыкальный сборник', '');
            } catch (Exception) {
                return new PageDTO('Неизвестный музыкальный сборник', '');
            }
        }

        return $this->inner->getPage($pageType, $pageId);
    }
}
