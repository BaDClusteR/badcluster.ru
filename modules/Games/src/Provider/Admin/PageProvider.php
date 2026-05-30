<?php

declare(strict_types=1);

namespace BC\Modules\Games\Provider\Admin;

use BC\DTO\PageDTO;
use BC\Modules\Blog\Model\Post;
use BC\Modules\Games\Model\GameMaterial;
use BC\Provider\Admin\IPageProvider;
use Runway\Exception\Exception;

readonly class PageProvider implements IPageProvider {
    public function __construct(
        private IPageProvider $inner
    ) {
    }

    public function getPage(string $pageType, int $pageId): PageDTO {
        if ($pageType === 'material') {
            try {
                $material = GameMaterial::findByUniqueIdentifier($pageId);

                return $material
                    ? new PageDTO(
                        sprintf('%s (%s)', $material->getTitle(), $material->getGame()->getTitle()),
                        "/admin/games/materials/{$material->getId()}"
                    )
                    : new PageDTO('Неизвестный игровой материал', '');
            } catch (Exception) {
                return new PageDTO('Неизвестный игровой материал', '');
            }
        }

        return $this->inner->getPage($pageType, $pageId);
    }
}
