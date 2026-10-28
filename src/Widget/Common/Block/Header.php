<?php

declare(strict_types=1);

namespace BC\Widget\Common\Block;

use BC\Core\Helper\MixedValue;
use BC\Core\Trait\TypographerTrait;
use BC\Widget\AWidget;

class Header extends AWidget {
    use TypographerTrait;

    protected function getTemplatePath(): string {
        return 'common/block/header.phtml';
    }

    protected function getText(): string {
        return $this->getTypographer()->process(
            MixedValue::toString($this->context['text'] ?? '')
        );
    }

    protected function getAnchor(): string {
        return MixedValue::toString($this->context['anchor'] ?? '');
    }

    protected function getLevel(): int {
        return MixedValue::toInt($this->context['level'] ?? 0);
    }
}
