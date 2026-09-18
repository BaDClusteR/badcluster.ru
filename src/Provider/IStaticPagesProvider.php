<?php

declare(strict_types=1);

namespace BC\Provider;

use BC\Model\StaticPage;

interface IStaticPagesProvider {
    public function getBySlug(string $slug, bool $onlyPublished): ?StaticPage;

    /**
     * @return StaticPage[]
     */
    public function getPublishedPages(): array;
}
