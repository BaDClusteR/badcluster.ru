<?php

declare(strict_types=1);

namespace BC\Api\DataBuilder\StaticPage;

use BC\Api\DTO\StaticPage\StaticPageDTO;
use BC\Api\DTO\StaticPage\StaticPageRowDTO;
use BC\Model\StaticPage;

interface IStaticPageDataBuilder {
    public function buildRow(StaticPage $page): StaticPageRowDTO;

    public function buildEntity(StaticPage $page): StaticPageDTO;
}
