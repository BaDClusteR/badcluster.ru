<?php

declare(strict_types=1);

namespace BC\Widget\Common\Block;

use BC\Core\Helper\MixedValue;
use BC\Core\Trait\TypographerTrait;
use BC\Widget\AWidget;

class Paragraph extends AWidget {
    use TypographerTrait;

    protected function getTemplatePath(): string {
        return 'common/block/paragraph.phtml';
    }

    protected function getContent(): string {
        return $this->getTypographer()->process(
            MixedValue::toString($this->context['text'] ?? '')
        );
    }

    protected function getAlignment(): string {
        $align = MixedValue::toString($this->context['alignment'] ?? '');

        return (in_array($align, ['center', 'right'], true))
            ? $align
            : '';
    }

    protected function isLead(): bool {
        return (bool) ($this->context['lead'] ?? false);
    }

    protected function getClassName(): string {
        $classes = [];

        if ($this->isLead()) {
            $classes[] = 'lead';
        }

        $align = $this->getAlignment();

        if ($align !== '') {
            $classes[] = "align-$align";
        }

        return implode(' ', $classes);
    }
}
