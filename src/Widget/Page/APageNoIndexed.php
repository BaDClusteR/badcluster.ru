<?php

namespace BC\Widget\Page;

use BC\Widget\DTO\MetaTagDTO;

abstract class APageNoIndexed extends APage {
    public function getMetaTags(): array {
        $tags = parent::getMetaTags();

        $tags[] = new MetaTagDTO('robots', 'noindex,nofollow');

        return $tags;
    }

    public function getMetaDescription(): string {
        return '';
    }
}
