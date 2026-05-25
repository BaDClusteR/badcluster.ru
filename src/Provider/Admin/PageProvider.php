<?php

declare(strict_types=1);

namespace BC\Provider\Admin;

use BC\DTO\PageDTO;

class PageProvider implements IPageProvider {
    public function getPage(string $pageType, int $pageId): PageDTO {
        return new PageDTO('', '');
    }
}
