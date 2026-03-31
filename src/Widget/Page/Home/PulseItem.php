<?php

namespace BC\Widget\Page\Home;

use BC\DTO\PulseItemDTO;
use BC\Widget\AWidget;

class PulseItem extends AWidget
{
    protected function getTemplatePath(): string
    {
        return 'home/pulse-item.phtml';
    }

    private function getItem(): ?PulseItemDTO {
        $item = $this->context['item'] ?? null;

        return $item instanceof PulseItemDTO
            ? $item
            : null;
    }

    protected function getUrl(): string {
        return (string)$this->getItem()?->url;
    }

    protected function isTall(): bool {
        return (bool)$this->getItem()?->isTall;
    }

    protected function isSurfaced(): bool {
        return (bool)$this->getItem()?->isSurfaced;
    }

    protected function getTag(): string {
        return (string)$this->getItem()?->tag;
    }

    protected function getTitle(): string {
        return (string)$this->getItem()?->title;
    }

    protected function getText(): string {
        return (string)$this->getItem()?->text;
    }

    protected function getStatus(): string {
        return (string)$this->getItem()?->status;
    }

    protected function getIcon(): string {
        return (string)$this->getItem()?->icon;
    }

    protected function getCssClass(): string {
        $classes = ['icon-button', 'grid__item'];

        if ($this->isTall()) {
            $classes[] = 'grid__item--tall';
        }

        if ($this->isSurfaced()) {
            $classes[] = 'grid__item--surfaced';
        }

        return implode(' ', $classes);
    }
}
