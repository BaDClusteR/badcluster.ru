<?php

namespace BC\Widget\Common\Block;

use BC\Widget\AWidget;
use Runway\Exception\Exception;

class Gallery extends AWidget
{

    protected function getTemplatePath(): string
    {
        return 'common/block/gallery.phtml';
    }

    /**
     * @return iterable<\BC\Model\Media>
     */
    protected function getSlides(): iterable {
        $mediaIds = array_map(
            static fn(array $media): int => (int)($media['id'] ?? 0),
            (array)($this->context['slides'] ?? [])
        );

        try {
            return \BC\Model\Media::find([
                'id' => $mediaIds
            ]);
        } catch (Exception) {
            return [];
        }
    }

    protected function getSlideMedia(array $slide): ?\BC\Model\Media {
        try {
            return \BC\Model\Media::findByUniqueIdentifier(
                (int) ($slide['id'] ?? 0)
            );
        } catch (Exception) {
            return null;
        }
    }


    protected function getCaption(int $index): string {
        return (string)($this->context['captions'][$index] ?? "");
    }

    protected function isFullWidth(): bool {
        return (bool)($this->context['fullWidth'] ?? false);
    }

    protected function isLightbox(): bool {
        return (bool)($this->context['lightbox'] ?? false);
    }

    protected function isLazy(): bool {
        return (bool)($this->context['lazy'] ?? false);
    }
}
