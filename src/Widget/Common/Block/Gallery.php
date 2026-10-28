<?php

declare(strict_types=1);

namespace BC\Widget\Common\Block;

use BC\Core\Helper\MixedValue;
use BC\Widget\AWidget;
use Runway\Exception\Exception;

class Gallery extends AWidget {
    protected function getTemplatePath(): string {
        return 'common/block/gallery.phtml';
    }

    /**
     * @return iterable<\BC\Model\Media>
     */
    protected function getSlides(): iterable {
        $mediaIds = array_map(
            static fn (mixed $media): int => is_array($media)
                ? MixedValue::toInt($media['id'] ?? 0)
                : 0,
            (array) ($this->context['slides'] ?? [])
        );

        try {
            return \BC\Model\Media::find([
                'id' => $mediaIds
            ]);
        } catch (Exception) {
            return [];
        }
    }

    /**
     * @param array<mixed> $slide ['id' => media id]
     */
    protected function getSlideMedia(array $slide): ?\BC\Model\Media {
        try {
            return \BC\Model\Media::findByUniqueIdentifier(
                MixedValue::toInt($slide['id'] ?? 0)
            );
        } catch (Exception) {
            return null;
        }
    }

    protected function getCaption(int $index): string {
        $captions = $this->context['captions'] ?? [];

        return is_array($captions)
            ? MixedValue::toString($captions[$index] ?? '')
            : '';
    }

    protected function isFullWidth(): bool {
        return (bool) ($this->context['fullWidth'] ?? false);
    }

    protected function isLightbox(): bool {
        return (bool) ($this->context['lightbox'] ?? true);
    }

    protected function isLazy(): bool {
        return (bool) ($this->context['lazy'] ?? false);
    }
}
