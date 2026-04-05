<?php

namespace BC\Widget\Page\Home;

use BC\Core\Trait\AttributesHelperTrait;
use BC\Core\Trait\WebsiteSettingsTrait;
use BC\DTO\PulseItemDTO;
use BC\Model\Media;
use BC\Widget\AWidget;
use BC\Widget\Common\Picture;

class PulseItem extends AWidget
{
    use AttributesHelperTrait;
    use WebsiteSettingsTrait;

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

    protected function getImage(): ?Media {
        return $this->getItem()?->image;
    }

    protected function getCssClass(): string {
        $classes = ['icon-button', 'grid__item'];

        if ($this->isTall()) {
            $classes[] = 'grid__item--tall';
        }

        if ($this->isSurfaced()) {
            $classes[] = 'grid__item--surfaced';
        }

        if ($this->getImage()) {
            $classes[] = 'grid__item--gallery';
        }

        return implode(' ', $classes);
    }

    protected function renderPicture(): string {
        if ($image = $this->getImage()) {
            return new Picture([
                'image'        => $image,
                'pictureClass' => 'grid__item-bg',
                'lazyLoad'     => true,
                'breakpoints'  => [
                    500 => 500,
                    700 => 1000,
                    -1  => 500
                ]
            ])->render();
        }

        return '';
    }

    protected function getContentContainerAttributes(): array {
        $result = [
            'class' => "grid__item-content"
        ];

        if ($this->getImage()) {
            $result['data-theme'] = 'dark';
        }

        return $result;
    }

    protected function getContainerAttributesAsString(): string {
        return $this->getAttributesHelper()->getAttributesAsString(
            $this->getContentContainerAttributes()
        );
    }
}
