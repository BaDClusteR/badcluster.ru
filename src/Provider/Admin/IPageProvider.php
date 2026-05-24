<?php

namespace BC\Provider\Admin;

use BC\DTO\PageDTO;

interface IPageProvider {
    public function getPage(string $pageType, int $pageId): PageDTO;
}
