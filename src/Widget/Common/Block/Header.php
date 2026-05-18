<?php

namespace BC\Widget\Common\Block;

use BC\Widget\AWidget;

class Header extends AWidget {
    protected function getTemplatePath(): string {
        return 'common/block/header.phtml';
    }

    protected function getText(): string {
        return (string) ($this->context['text'] ?? '');
    }

    protected function getAnchor(): string {
        return (string) ($this->context['anchor'] ?? '');
    }

    protected function getLevel(): int {
        return (int) ($this->context['level'] ?? 0);
    }
}
